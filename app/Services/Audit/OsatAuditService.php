<?php

namespace App\Services\Audit;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Process\Process;

class OsatAuditService
{
    private Client $httpClient;
    private ?string $psiApiKey;

    public function __construct(?Client $httpClient = null)
    {
        $this->httpClient = $httpClient ?? new Client([
            'timeout' => 20,
            'verify' => false,
            'http_errors' => false,
            'allow_redirects' => true,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ],
        ]);
        $this->psiApiKey = env('PAGE_SPEED_API_KEY') ?: env('GOOGLE_PSI_API_KEY');
    }

    /**
     * Run the OSAT-style probes in one pass.
     */
    public function run(string $url, int $internalLimit = 75, int $keywordTop = 20): array
    {
        $page = $this->fetchPage($url);
        $html = $page['html'] ?? '';

        $lighthouseMobile = $this->runPageSpeedApi($url, 'mobile') ?? $this->runLighthouse($url, 'mobile');
        $lighthouseDesktop = $this->runPageSpeedApi($url, 'desktop') ?? $this->runLighthouse($url, 'desktop');

        return [
            'lighthouse' => [
                'mobile' => $lighthouseMobile,
                'desktop' => $lighthouseDesktop,
            ],
            'security' => $this->runSecurityScan($url),
            'extractor' => [
                'headers' => $this->extractHeaders($html),
                'images' => $this->extractImages($html, $url),
                'links' => $this->extractLinks($html, $url, 120),
            ],
            'sitemap' => $this->extractSitemap($url),
            'internal_links' => $this->crawlInternalLinks($url, $internalLimit),
            'keywords' => $this->extractKeywords($html, 3, $keywordTop),
            'summary' => $this->summarizeText($html),
            'page' => $page,
        ];
    }

    private function runLighthouse(string $url, string $preset = 'mobile'): array
    {
        $command = [
            'lighthouse',
            "--chrome-flags=--headless --no-sandbox --disable-dev-shm-usage",
            $url,
            '--output=json',
            '--output-path=stdout',
        ];

        if ($preset === 'desktop') {
            $command[] = '--preset=desktop';
        }

        $process = new Process($command);
        $process->setTimeout(180);

        try {
            $process->run();
        } catch (\Throwable $e) {
            return ['error' => 'Lighthouse failed to start: '.$e->getMessage()];
        }

        if (! $process->isSuccessful()) {
            return [
                'error' => 'Lighthouse failed',
                'output' => $process->getErrorOutput() ?: $process->getOutput(),
            ];
        }

        $decoded = json_decode($process->getOutput(), true);
        if (! $decoded) {
            return ['error' => 'Unable to parse lighthouse output'];
        }

        $categories = $decoded['categories'] ?? [];

        $audits = $decoded['audits'] ?? [];

        return [
            'preset' => $preset,
            'scores' => [
                'performance' => $categories['performance']['score'] ?? null,
                'accessibility' => $categories['accessibility']['score'] ?? null,
                'best_practices' => $categories['best-practices']['score'] ?? null,
                'seo' => $categories['seo']['score'] ?? null,
                'pwa' => $categories['pwa']['score'] ?? null,
            ],
            'metrics' => [
                'first_contentful_paint_ms' => $this->getAuditNumeric($audits, 'first-contentful-paint'),
                'largest_contentful_paint_ms' => $this->getAuditNumeric($audits, 'largest-contentful-paint'),
                'speed_index_ms' => $this->getAuditNumeric($audits, 'speed-index'),
                'total_blocking_time_ms' => $this->getAuditNumeric($audits, 'total-blocking-time'),
                'time_to_interactive_ms' => $this->getAuditNumeric($audits, 'interactive'),
                'cumulative_layout_shift' => $audits['cumulative-layout-shift']['numericValue'] ?? null,
            ],
            'timing' => $decoded['timing'] ?? null,
            'fetched_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Try to fetch metrics from the PageSpeed Insights API for closer parity with PSI UI.
     */
    private function runPageSpeedApi(string $url, string $strategy = 'mobile'): ?array
    {
        if (! $this->psiApiKey) {
            return null;
        }

        $endpoint = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';
        $params = http_build_query([
            'url' => $url,
            'strategy' => $strategy, // PSI expects lowercase "mobile" or "desktop"
            'category' => 'performance',
            'key' => $this->psiApiKey,
        ]);

        try {
            $response = $this->httpClient->get($endpoint.'?'.$params, ['timeout' => 180]);
            if ($response->getStatusCode() !== 200) {
                return null;
            }
            $body = json_decode((string) $response->getBody(), true);
            if (! $body || ! isset($body['lighthouseResult'])) {
                return null;
            }

            $lh = $body['lighthouseResult'];
            $categories = $lh['categories'] ?? [];
            $audits = $lh['audits'] ?? [];

            return [
                'preset' => $strategy,
                'source' => 'psi_api',
                'scores' => [
                    'performance' => $categories['performance']['score'] ?? null,
                    'accessibility' => $categories['accessibility']['score'] ?? null,
                    'best_practices' => $categories['best-practices']['score'] ?? null,
                    'seo' => $categories['seo']['score'] ?? null,
                    'pwa' => $categories['pwa']['score'] ?? null,
                ],
                'metrics' => [
                    'first_contentful_paint_ms' => $this->getAuditNumeric($audits, 'first-contentful-paint'),
                    'largest_contentful_paint_ms' => $this->getAuditNumeric($audits, 'largest-contentful-paint'),
                    'speed_index_ms' => $this->getAuditNumeric($audits, 'speed-index'),
                    'total_blocking_time_ms' => $this->getAuditNumeric($audits, 'total-blocking-time'),
                    'time_to_interactive_ms' => $this->getAuditNumeric($audits, 'interactive'),
                    'cumulative_layout_shift' => $audits['cumulative-layout-shift']['numericValue'] ?? null,
                ],
                'fetched_at' => now()->toIso8601String(),
            ];
        } catch (GuzzleException) {
            return null;
        }
    }

    private function getAuditNumeric(array $audits, string $key): ?float
    {
        return isset($audits[$key]['numericValue']) ? (float) $audits[$key]['numericValue'] : null;
    }

    private function runSecurityScan(string $url): array
    {
        $process = new Process([
            'httpobs-cli',
            '-d',
            $url,
        ]);
        $process->setTimeout(180);

        try {
            $process->run();
        } catch (\Throwable $e) {
            return ['error' => 'HTTP Observatory failed to start: '.$e->getMessage()];
        }

        if (! $process->isSuccessful()) {
            return [
                'error' => 'HTTP Observatory failed',
                'output' => $process->getErrorOutput() ?: $process->getOutput(),
            ];
        }

        $raw = json_decode($process->getOutput(), true);
        if (! $raw) {
            return ['error' => 'Unable to parse HTTP Observatory output'];
        }

        $computed = [
            'score' => $raw['scan']['score'] ?? null,
            'grade' => $raw['scan']['grade'] ?? null,
            'status_code' => $raw['scan']['status_code'] ?? null,
            'tests_failed' => $raw['scan']['tests_failed'] ?? null,
            'tests_passed' => $raw['scan']['tests_passed'] ?? null,
            'tests_quantity' => $raw['scan']['tests_quantity'] ?? null,
            'response_headers' => [],
            'tests' => [],
            'fetched_at' => now()->toIso8601String(),
        ];

        foreach (($raw['scan']['response_headers'] ?? []) as $name => $value) {
            $computed['response_headers'][] = [
                'name' => $name,
                'value' => $value,
            ];
        }

        foreach (($raw['tests'] ?? []) as $test) {
            $computed['tests'][] = [
                'name' => $test['name'] ?? null,
                'pass' => $test['pass'] ?? null,
                'result' => $test['result'] ?? null,
                'expectation' => $test['expectation'] ?? null,
                'score_description' => $test['score_description'] ?? null,
            ];
        }

        return $computed;
    }

    private function fetchPage(string $url): array
    {
        try {
            $response = $this->httpClient->get($url);
            $html = (string) $response->getBody();

            return [
                'status_code' => $response->getStatusCode(),
                'url' => $url,
                'html' => $html,
            ];
        } catch (GuzzleException $e) {
            return [
                'status_code' => 0,
                'url' => $url,
                'html' => '',
                'error' => 'Failed to fetch page: '.$e->getMessage(),
            ];
        }
    }

    private function extractHeaders(string $html): array
    {
        $result = [
            'h1' => ['count' => 0, 'values' => []],
            'h2' => ['count' => 0, 'values' => []],
            'h3' => ['count' => 0, 'values' => []],
            'h4' => ['count' => 0, 'values' => []],
            'h5' => ['count' => 0, 'values' => []],
            'h6' => ['count' => 0, 'values' => []],
        ];

        if (empty($html)) {
            return $result;
        }

        $dom = $this->createDom($html);
        if (! $dom) {
            return $result;
        }

        $xpath = new \DOMXPath($dom);
        foreach (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $tag) {
            $nodes = $xpath->query('//'.$tag);
            foreach ($nodes as $node) {
                $text = trim($node->textContent);
                $result[$tag]['values'][] = $text;
                $result[$tag]['count']++;
            }
        }

        return $result;
    }

    private function extractImages(string $html, string $baseUrl): array
    {
        $result = [
            'images' => [],
            'summary' => [
                'missing_title' => 0,
                'missing_alt' => 0,
                'duplicates' => 0,
                'total' => 0,
            ],
        ];

        if (empty($html)) {
            return $result;
        }

        $dom = $this->createDom($html);
        if (! $dom) {
            return $result;
        }

        $xpath = new \DOMXPath($dom);
        $seen = [];
        foreach ($xpath->query('//img') as $img) {
            $src = $img->getAttribute('src')
                ?: $img->getAttribute('data-src')
                ?: $img->getAttribute('src-set');

            if (! $src) {
                continue;
            }

            $url = $this->resolveUrl($baseUrl, $src);
            if (! $url) {
                continue;
            }

            $alt = $img->getAttribute('alt') ?: null;
            $title = $img->getAttribute('title') ?: null;
            $result['summary']['total']++;

            if (! $alt) {
                $result['summary']['missing_alt']++;
            }

            if (! $title) {
                $result['summary']['missing_title']++;
            }

            if (isset($seen[$url])) {
                $result['summary']['duplicates']++;
                continue;
            }

            $seen[$url] = true;
            $result['images'][] = [
                'url' => $url,
                'alt' => $alt,
                'title' => $title,
            ];
        }

        return $result;
    }

    private function extractLinks(string $html, string $baseUrl, int $maxLinks = 120): array
    {
        $statusBuckets = [];
        if (empty($html)) {
            return $statusBuckets;
        }

        $dom = $this->createDom($html);
        if (! $dom) {
            return $statusBuckets;
        }

        $xpath = new \DOMXPath($dom);
        $visited = [];

        foreach ($xpath->query('//a[@href]') as $anchor) {
            if (count($visited) >= $maxLinks) {
                break;
            }

            $href = $anchor->getAttribute('href');
            $resolved = $this->resolveUrl($baseUrl, $href);
            if (! $resolved || isset($visited[$resolved])) {
                continue;
            }

            $visited[$resolved] = true;
            $status = $this->getStatusCode($resolved);
            $statusBuckets[$status] = $statusBuckets[$status] ?? [];
            $statusBuckets[$status][] = $resolved;
        }

        return $statusBuckets;
    }

    private function extractSitemap(string $siteUrl): array
    {
        $candidates = [
            $siteUrl,
            rtrim($siteUrl, '/').'/sitemap.xml',
        ];

        $visited = [];
        $results = [];
        $id = 0;

        foreach ($candidates as $candidate) {
            $this->parseSitemap($candidate, $visited, $results, $id);
            if (! empty($results)) {
                break;
            }
        }

        return $results ?: ['error' => 'No valid sitemap found'];
    }

    private function parseSitemap(string $url, array &$visited, array &$results, int &$id): void
    {
        if (isset($visited[$url])) {
            return;
        }
        $visited[$url] = true;

        try {
            $response = $this->httpClient->get($url);
            if ($response->getStatusCode() !== 200) {
                return;
            }

            $xml = @simplexml_load_string((string) $response->getBody());
            if (! $xml) {
                return;
            }

            if ($xml->getName() === 'sitemapindex') {
                foreach ($xml->sitemap as $sitemap) {
                    $loc = trim((string) $sitemap->loc);
                    $this->parseSitemap($loc, $visited, $results, $id);
                }
            }

            if ($xml->getName() === 'urlset') {
                foreach ($xml->url as $urlNode) {
                    $loc = trim((string) $urlNode->loc);
                    $lastmod = trim((string) $urlNode->lastmod);
                    $results[] = [
                        'id' => $id++,
                        'url' => $loc,
                        'last_modified' => $lastmod ?: null,
                    ];
                }
            }
        } catch (GuzzleException $e) {
            // Swallow and continue to the next candidate.
        }
    }

    private function crawlInternalLinks(string $rootUrl, int $maximum): array
    {
        $domain = parse_url($rootUrl, PHP_URL_HOST);
        if (! $domain) {
            return ['error' => 'Invalid root URL'];
        }

        $queue = [$rootUrl];
        $visited = [];
        $edges = [];
        $status = [
            'pages_crawled' => 0,
            'broken_links' => 0,
        ];

        while (! empty($queue) && count($visited) < $maximum) {
            $current = array_shift($queue);
            if (isset($visited[$current])) {
                continue;
            }
            $visited[$current] = true;
            $status['pages_crawled']++;

            $page = $this->fetchPage($current);
            if (empty($page['html'])) {
                continue;
            }

            $dom = $this->createDom($page['html']);
            if (! $dom) {
                continue;
            }

            $xpath = new \DOMXPath($dom);
            foreach ($xpath->query('//a[@href]') as $anchor) {
                $href = $anchor->getAttribute('href');
                $resolved = $this->resolveUrl($current, $href);
                if (! $resolved) {
                    continue;
                }

                $host = parse_url($resolved, PHP_URL_HOST);
                if ($host !== $domain) {
                    continue;
                }

                $edges[] = [
                    'from' => $this->extractPath($current),
                    'to' => $this->extractPath($resolved),
                ];

                if (! isset($visited[$resolved]) && ! in_array($resolved, $queue, true) && count($visited) + count($queue) < $maximum) {
                    $queue[] = $resolved;
                }
            }
        }

        $degrees = [];
        foreach ($edges as $edge) {
            $degrees[$edge['from']] = ($degrees[$edge['from']] ?? 0) + 1;
            $degrees[$edge['to']] = ($degrees[$edge['to']] ?? 0) + 1;
        }

        $nodes = [];
        foreach ($degrees as $node => $degree) {
            $nodes[] = [
                'url' => $node,
                'degree' => $degree,
            ];
        }

        return [
            'nodes' => $nodes,
            'edges' => $edges,
            'summary' => [
                'pages_crawled' => $status['pages_crawled'],
                'unique_nodes' => count($nodes),
            ],
        ];
    }

    private function extractKeywords(string $html, int $ngram = 3, int $top = 20): array
    {
        $text = strtolower(strip_tags($html ?? ''));
        $text = preg_replace('/\s+/', ' ', $text ?? '');
        if (! $text) {
            return [];
        }

        $tokens = preg_split('/[^a-z0-9]+/i', $text, -1, PREG_SPLIT_NO_EMPTY);
        $tokens = array_values(array_filter($tokens, fn ($token) => ! $this->isStopWord($token)));

        $scores = [];
        $length = count($tokens);
        for ($n = 1; $n <= $ngram; $n++) {
            for ($i = 0; $i <= $length - $n; $i++) {
                $ng = implode(' ', array_slice($tokens, $i, $n));
                $scores[$ng] = ($scores[$ng] ?? 0) + 1;
            }
        }

        arsort($scores);
        $topScores = array_slice($scores, 0, $top, true);

        $id = 0;
        $result = [];
        foreach ($topScores as $phrase => $score) {
            $result[] = [
                'id' => $id++,
                'ngram' => $phrase,
                'score' => $score,
            ];
        }

        return $result;
    }

    private function summarizeText(string $html, int $sentences = 3): ?string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($html ?? '')));
        if (! $text) {
            return null;
        }

        $parts = preg_split('/(?<=[.!?])\s+/', $text);
        $summary = array_slice($parts, 0, $sentences);

        return implode(' ', $summary);
    }

    private function isStopWord(string $token): bool
    {
        static $stopWords = [
            'the', 'and', 'for', 'are', 'with', 'this', 'that', 'was', 'were', 'will', 'would',
            'shall', 'should', 'can', 'could', 'has', 'have', 'had', 'but', 'not', 'you', 'your',
            'yours', 'their', 'there', 'they', 'them', 'our', 'ours', 'his', 'her', 'hers', 'its',
            'from', 'into', 'about', 'after', 'before', 'over', 'under', 'again', 'further',
            'then', 'once', 'here', 'there', 'when', 'where', 'why', 'how', 'all', 'any', 'both',
            'each', 'few', 'more', 'most', 'other', 'some', 'such', 'no', 'nor', 'only', 'own',
            'same', 'so', 'than', 'too', 'very', 's', 't', 'just', 'don', 'now',
        ];

        return in_array($token, $stopWords, true);
    }

    private function createDom(string $html): ?\DOMDocument
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML($html);
        libxml_clear_errors();

        return $loaded ? $dom : null;
    }

    private function resolveUrl(string $base, string $href): ?string
    {
        if (str_starts_with($href, '//')) {
            $scheme = parse_url($base, PHP_URL_SCHEME) ?: 'https';
            return $scheme.':'.$href;
        }

        if (parse_url($href, PHP_URL_SCHEME)) {
            return $href;
        }

        if (str_starts_with($href, '#') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:')) {
            return null;
        }

        $baseParts = parse_url($base);
        if (! isset($baseParts['scheme'], $baseParts['host'])) {
            return null;
        }

        $path = $baseParts['path'] ?? '/';
        $dir = rtrim(str_replace(basename($path), '', $path), '/');
        $fullPath = $href;

        if (! str_starts_with($href, '/')) {
            $fullPath = ($dir ? '/'.$dir : '').'/'.$href;
        }

        $resolved = $baseParts['scheme'].'://'.$baseParts['host'].$fullPath;
        $resolved = preg_replace('#/+#', '/', $resolved);
        $resolved = preg_replace('#https:/#', 'https://', $resolved);
        $resolved = preg_replace('#http:/#', 'http://', $resolved);

        return $resolved;
    }

    private function extractPath(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        return $path === '' ? '/' : $path;
    }

    private function getStatusCode(string $url): int
    {
        try {
            $response = $this->httpClient->head($url);
            return $response->getStatusCode();
        } catch (GuzzleException) {
            try {
                $response = $this->httpClient->get($url);
                return $response->getStatusCode();
            } catch (GuzzleException) {
                return 500;
            }
        }
    }
}
