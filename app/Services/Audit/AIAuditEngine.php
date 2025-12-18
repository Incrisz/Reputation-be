<?php

namespace App\Services\Audit;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use App\Services\Audit\OsatAuditService;
use App\Services\Audit\OpenAIService;

class AIAuditEngine
{
    private Client $httpClient;

    private Client $openAIClient;

    private string $apiKey;

    private string $model;

    private OsatAuditService $osatAuditService;

    private OpenAIService $openAIService;

    private string $desktopUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
    private string $mobileUserAgent = 'Mozilla/5.0 (Linux; Android 10; SM-G973F) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36';

    private ?string $serperApiKey = null;

    private ?string $googlePlacesApiKey = null;

    private array $socialPlatforms = [
        'facebook' => 'facebook.com',
        'instagram' => 'instagram.com',
        'linkedin' => 'linkedin.com',
        'youtube' => 'youtube.com',
        'twitter' => 'x.com',
        'tiktok' => 'tiktok.com',
    ];

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key', env('OPENAI_API_KEY'));
        $this->model = config('services.openai.model', env('OPENAI_MODEL', 'gpt-4o-mini'));

        $this->httpClient = new Client([
            'timeout' => 30,
            'verify' => false,
            'headers' => [
                'User-Agent' => $this->desktopUserAgent,
            ],
        ]);

        $this->osatAuditService = new OsatAuditService($this->httpClient);
        $this->openAIService = new OpenAIService();

        $this->openAIClient = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'timeout' => 180,
            'headers' => [
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
            ],
        ]);

        $serperKey = config('services.serper.api_key', env('SERPER_API_KEY'));
        $googlePlacesKey = config('services.google_places.api_key', env('GOOGLE_PLACES_API_KEY'));

        $this->serperApiKey = is_string($serperKey) && trim($serperKey) !== '' ? trim($serperKey) : null;
        $this->googlePlacesApiKey = is_string($googlePlacesKey) && trim($googlePlacesKey) !== '' ? trim($googlePlacesKey) : null;
    }

    /**
     * Run comprehensive AI-powered audit
     */
    public function runComprehensiveAudit(array $input): array
    {
        // Manual mode: fetch website content, run OSAT probes, and enrich with SERPER + Google Places data
        $websiteContent = $this->fetchWebsiteContent($input['website_url']);
        $manualResults = $this->buildManualAuditResults($input, $websiteContent);

        $osatChecks = $this->osatAuditService->run($input['website_url']);
        $manualResults['osat_checks'] = $osatChecks;

        $manualResults['website_audit']['technical_seo']['page_speed_estimate'] = [
            'desktop_ms' => $this->resolvePageSpeed($osatChecks, 'desktop', $websiteContent['response_time_ms_desktop'] ?? null),
            'mobile_ms' => $this->resolvePageSpeed($osatChecks, 'mobile', $websiteContent['response_time_ms_mobile'] ?? null),
        ];
        $manualResults['website_audit']['technical_seo']['mobile_friendly'] = $this->resolveMobileFriendly(
            $osatChecks,
            $websiteContent['html_preview'] ?? ''
        );

        $aiRecommendations = $this->openAIService->generateRecommendations($manualResults, $input);
        $manualResults['ai_recommendations'] = [
            'content' => $aiRecommendations['recommendations'] ?? null,
            'success' => $aiRecommendations['success'] ?? false,
            'model_used' => $aiRecommendations['model_used'] ?? null,
            'tokens_used' => $aiRecommendations['tokens_used'] ?? null,
            'note' => $aiRecommendations['note'] ?? null,
            'error' => $aiRecommendations['error'] ?? null,
        ];

        return [
            'success' => true,
            'audit_results' => $manualResults,
            'metadata' => [
                'model_used' => $aiRecommendations['model_used'] ?? null,
                'tokens_used' => $aiRecommendations['tokens_used'] ?? null,
                'audit_method' => 'manual_fetch_with_osat_probes_and_ai_recommendations',
                'timestamp' => now()->toIso8601String(),
                'note' => $this->buildMetadataNote($aiRecommendations),
            ],
        ];
    }

    private function buildMetadataNote(array $aiRecommendations): string
    {
        $base = 'Social media discovery leverages website parsing + SERPER; Google Business Profile detection via Places API. OSAT-style probes added (lighthouse/security/extractor/sitemap/internal/keywords). ';

        if (($aiRecommendations['success'] ?? false) === true) {
            return $base.'AI recommendations generated via OpenAI.';
        }

        $fallback = $aiRecommendations['note'] ?? $aiRecommendations['error'] ?? 'AI recommendations unavailable.';
        return $base.'AI recommendations fallback: '.$fallback;
    }

    /**
     * Fetch website content for analysis
     */
    private function fetchWebsiteContent(string $url): array
    {
        $start = microtime(true);
        try {
            $response = $this->httpClient->get($url);
            $html = (string) $response->getBody();
            $elapsedMs = round((microtime(true) - $start) * 1000, 1);
            $mobileElapsed = $this->measureResponseTime($url, $this->mobileUserAgent);

            // Extract key information
            $data = [
                'status_code' => $response->getStatusCode(),
                'has_ssl' => str_starts_with($url, 'https://'),
                'html_length' => strlen($html),
                'html_preview' => substr($html, 0, 8000), // First 8000 chars for AI analysis
                'response_time_ms_desktop' => $elapsedMs,
                'response_time_ms_mobile' => $mobileElapsed,
            ];

            // Check for common resources
            $data['has_robots'] = $this->checkResource($url.'/robots.txt');
            $data['has_sitemap'] = $this->checkResource($url.'/sitemap.xml');

            return $data;

        } catch (GuzzleException $e) {
            return [
                'status_code' => 0,
                'error' => 'Failed to fetch website: '.$e->getMessage(),
                'has_ssl' => str_starts_with($url, 'https://'),
                'response_time_ms_desktop' => null,
                'response_time_ms_mobile' => null,
            ];
        }
    }

    /**
     * Check if a resource exists
     */
    private function checkResource(string $url): bool
    {
        try {
            $response = $this->httpClient->head($url);

            return $response->getStatusCode() === 200;
        } catch (GuzzleException $e) {
            return false;
        }
    }

    /**
     * Search for social media profiles on the web
     */
    private function searchSocialMediaProfiles(array $input): array
    {
        $businessName = $input['business_name'];
        $cities = is_array($input['city']) ? implode(' ', $input['city']) : $input['city'];
        $countries = is_array($input['country']) ? implode(' ', $input['country']) : $input['country'];
        $location = trim($cities.' '.$countries);

        $platforms = [
            'facebook' => [
                'search_url' => 'https://www.facebook.com/public/'.urlencode($businessName),
                'search_query' => $businessName.' '.$location.' facebook',
            ],
            'instagram' => [
                'search_url' => 'https://www.instagram.com/explore/tags/'.urlencode(str_replace(' ', '', strtolower($businessName))),
                'search_query' => $businessName.' '.$location.' instagram',
            ],
            'twitter' => [
                'search_url' => 'https://twitter.com/search?q='.urlencode($businessName.' '.$location),
                'search_query' => $businessName.' '.$location.' twitter OR x.com',
            ],
            'linkedin' => [
                'search_url' => 'https://www.linkedin.com/search/results/companies/?keywords='.urlencode($businessName),
                'search_query' => $businessName.' '.$location.' linkedin company',
            ],
            'tiktok' => [
                'search_url' => 'https://www.tiktok.com/search?q='.urlencode($businessName),
                'search_query' => $businessName.' '.$location.' tiktok',
            ],
        ];

        $searchResults = [];

        foreach ($platforms as $platform => $searchData) {
            try {
                // Attempt to search via DuckDuckGo
                $duckDuckGoUrl = 'https://html.duckduckgo.com/html/?q='.urlencode($searchData['search_query']);

                $response = $this->httpClient->get($duckDuckGoUrl, [
                    'timeout' => 10,
                    'allow_redirects' => true,
                ]);

                $html = (string) $response->getBody();

                $searchResults[$platform] = [
                    'search_performed' => true,
                    'search_query' => $searchData['search_query'],
                    'search_url' => $searchData['search_url'],
                    'results_html_preview' => substr($html, 0, 3000), // First 3000 chars
                    'results_found' => strlen($html) > 500, // Basic check if results exist
                ];

            } catch (GuzzleException $e) {
                $searchResults[$platform] = [
                    'search_performed' => false,
                    'error' => 'Search failed: '.$e->getMessage(),
                    'search_query' => $searchData['search_query'],
                ];
            }
        }

        return $searchResults;
    }

    /**
     * Format social media search results for the prompt
     */
    private function formatSocialMediaSearchResults(array $searchResults): string
    {
        $formatted = "The following web searches were performed to detect social media presence:\n\n";

        foreach ($searchResults as $platform => $data) {
            $formatted .= '## '.strtoupper($platform)."\n";

            if ($data['search_performed']) {
                $formatted .= "- Search Query: {$data['search_query']}\n";
                $formatted .= "- Platform URL: {$data['search_url']}\n";
                $formatted .= '- Results Found: '.($data['results_found'] ? 'Yes' : 'No')."\n";
                $formatted .= "- Search Results Preview (first 3000 chars):\n";
                $formatted .= "```\n{$data['results_html_preview']}\n```\n\n";
            } else {
                $formatted .= "- Search Failed: {$data['error']}\n\n";
            }
        }

        return $formatted;
    }

    /**
     * Format Google Business search results for the prompt
     */
    private function formatGoogleBusinessSearchResults(array $searchResults): string
    {
        $formatted = "The following web searches were performed to detect Google Business Profile:\n\n";

        foreach ($searchResults as $queryType => $data) {
            $formatted .= '## '.strtoupper(str_replace('_', ' ', $queryType))." SEARCH\n";

            if ($data['search_performed']) {
                $formatted .= "- Search Query: {$data['search_query']}\n";
                $formatted .= '- Has Google Maps Link: '.($data['has_google_maps_link'] ? 'Yes' : 'No')."\n";
                $formatted .= '- Has Business Link: '.($data['has_business_link'] ? 'Yes' : 'No')."\n";
                $formatted .= '- Has Reviews Mention: '.($data['has_reviews_mention'] ? 'Yes' : 'No')."\n";
                $formatted .= "- Search Results Preview (first 2000 chars):\n";
                $formatted .= "```\n{$data['results_html_preview']}\n```\n\n";
            } else {
                $formatted .= "- Search Failed: {$data['error']}\n\n";
            }
        }

        return $formatted;
    }

    /**
     * Build comprehensive audit prompt for AI
     */
    private function buildAuditPrompt(array $input, array $websiteContent, array $socialMediaSearchResults, array $googleBusinessSearchResults): string
    {
        $cities = is_array($input['city']) ? implode(', ', $input['city']) : $input['city'];
        $countries = is_array($input['country']) ? implode(', ', $input['country']) : $input['country'];
        $keywords = ! empty($input['keywords']) ? implode(', ', $input['keywords']) : 'N/A';
        $competitors = ! empty($input['competitors']) ? implode(', ', $input['competitors']) : 'N/A';

        $htmlPreview = $websiteContent['html_preview'] ?? 'Website content not available';

        // Format search results for the prompt
        $socialMediaSearchSummary = $this->formatSocialMediaSearchResults($socialMediaSearchResults);
        $googleBusinessSearchSummary = $this->formatGoogleBusinessSearchResults($googleBusinessSearchResults);

        return <<<PROMPT
You are conducting a comprehensive business visibility audit. Analyze the provided information and return a detailed, structured JSON response.

# BUSINESS INFORMATION
- Business Name: {$input['business_name']}
- Industry: {$input['industry']}
- Website: {$input['website_url']}
- Locations: {$cities}, {$countries}
- Target Audience: {$input['target_audience']}
- Keywords: {$keywords}
- Competitors: {$competitors}

# WEBSITE TECHNICAL DATA
- SSL: {$websiteContent['has_ssl']}
- Robots.txt: {$websiteContent['has_robots']}
- Sitemap.xml: {$websiteContent['has_sitemap']}
- Status Code: {$websiteContent['status_code']}

# WEBSITE HTML CONTENT (first 8000 chars)
{$htmlPreview}

# WEB SEARCH RESULTS FOR SOCIAL MEDIA PLATFORMS
{$socialMediaSearchSummary}

# WEB SEARCH RESULTS FOR GOOGLE BUSINESS PROFILE
{$googleBusinessSearchSummary}

# YOUR TASK
Analyze ALL the provided information (website content, web search results for social media, and Google Business search results) to produce a comprehensive audit covering:

1. **Website Audit & SEO Analysis** - Evaluate technical SEO, content quality, local SEO signals, security, UX, indexability, and brand consistency
2. **Social Media Detection** - IMPORTANT: Use the WEB SEARCH RESULTS provided above to detect social media presence. DO NOT rely solely on website links. Analyze the search results HTML previews to determine if the business actually has profiles on each platform. Only mark a platform as "present: true" if you find strong evidence in the search results, not just because a link exists on the website.
3. **Google Business Profile Assessment** - Use the WEB SEARCH RESULTS to determine if the business has a Google Business Profile. Look for Google Maps links, review mentions, and business listings in the search results.
4. **Visibility Score** - Calculate scores (0-100) for website, social media, and overall online visibility based on actual findings
5. **Actionable Recommendations** - Provide specific, prioritized recommendations for improvement

# REQUIRED JSON OUTPUT STRUCTURE
Return ONLY a valid JSON object (no markdown, no code blocks, no extra text) with this exact structure:

{
  "website_audit": {
    "technical_seo": {
      "score": 0-100,
      "ssl_valid": boolean,
      "robots_txt_present": boolean,
      "sitemap_xml_present": boolean,
      "page_speed_estimate": "fast/medium/slow",
      "mobile_friendly": boolean,
      "issues": ["array of issues found"],
      "strengths": ["array of positive findings"]
    },
      "content_quality": {
        "score": 0-100,
        "has_meta_title": boolean,
        "has_meta_description": boolean,
        "meta_title": "extracted title or null",
      "meta_description": "extracted description or null",
      "keyword_usage": "good/fair/poor/unknown",
      "issues": ["array of content issues"],
      "strengths": ["array of content strengths"]
    },
  "security_trust": {
    "score": 0-100,
    "ssl_certificate": boolean,
    "privacy_policy_found": boolean,
    "terms_conditions_found": boolean,
    "contact_info_visible": boolean,
    "issues": ["array of security/trust issues"]
  },
  },
  "social_media_presence": {
    "business_name": "string",
    "website": "string",
    "platforms": {
      "facebook": {
        "url": "resolved URL or null/NOT FOUND",
        "source": "website/search/none",
        "confidence": "HIGH/LOW/NONE"
      },
      "instagram": {
        "url": "resolved URL or null/NOT FOUND",
        "source": "website/search/none",
        "confidence": "HIGH/LOW/NONE"
      },
      "twitter": {
        "url": "resolved URL or null/NOT FOUND",
        "source": "website/search/none",
        "confidence": "HIGH/LOW/NONE"
      },
      "linkedin": {
        "url": "resolved URL or null/NOT FOUND",
        "source": "website/search/none",
        "confidence": "HIGH/LOW/NONE"
      },
      "youtube": {
        "url": "resolved URL or null/NOT FOUND",
        "source": "website/search/none",
        "confidence": "HIGH/LOW/NONE"
      },
      "tiktok": {
        "url": "resolved URL or null/NOT FOUND",
        "source": "website/search/none",
        "confidence": "HIGH/LOW/NONE"
      }
    },
    "social_score": 0-100,
    "total_platforms": number,
    "integration_quality": "excellent/good/fair/poor",
    "recommendations": ["array of social media recommendations"]
  },
  "google_business_profile": {
    "found": "YES/NO/UNKNOWN",
    "name": "string or N/A",
    "address": "string or N/A",
    "phone": "string or N/A",
    "rating": number or "N/A",
    "reviews": number or "N/A",
    "confidence": "very_high/high/medium/low",
    "score": 0-100
  },
  "visibility_scores": {
    "website_audit": 0-100,
    "content_quality": 0-100,
    "social_media_presence": 0-100,
    "google_business_profile": 0-100,
    "overall_visibility_score": 0-100,
    "grade": "A/B/C/D/E/F",
    "grade_description": "Narrative explaining the overall grade"
  },
  "key_findings": {
    "strengths": ["top 3-5 strengths"],
    "weaknesses": ["top 3-5 weaknesses"],
    "opportunities": ["top 3-5 opportunities"],
    "threats": ["top 3-5 threats or risks"]
  },
  "recommendations": {
    "immediate_actions": [
      {
        "priority": "high/medium/low",
        "category": "seo/social/local/content/technical",
        "action": "specific action to take",
        "impact": "high/medium/low",
        "effort": "low/medium/high",
        "description": "detailed explanation"
      }
    ],
    "short_term_strategy": ["actions for next 1-3 months"],
    "long_term_strategy": ["actions for 3-6+ months"],
    "quick_wins": ["easy wins for immediate impact"]
  },
  "competitive_insights": {
    "market_position_estimate": "leader/strong/moderate/weak",
    "differentiation_opportunities": ["array of opportunities"],
    "competitive_advantages": ["identified advantages"],
    "areas_for_improvement": ["compared to typical competitors"]
  }
}

IMPORTANT: Return ONLY the JSON object. Do not include markdown code blocks, explanations, or any text outside the JSON structure.
PROMPT;
    }

    /**
     * Manual-only audit using fetched website content
     */
    private function buildManualAuditResults(array $input, array $websiteContent): array
    {
        $html = $websiteContent['html_preview'] ?? '';
        $textContent = $html ? strip_tags($html) : '';
        // Temporarily disable word count usage (rendered content may be low/JS-driven)
        $wordCount = null;
        $wordCountScoreInput = 0;

        $metaTitle = null;
        if ($html && preg_match('/<title[^>]*>([^<]*)<\\/title>/i', $html, $match)) {
            $metaTitle = trim($match[1]);
        }

        $metaDescription = null;
        if ($html && preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']*)["\']/i', $html, $match)) {
            $metaDescription = trim($match[1]);
        }

        $hasMetaTitle = ! empty($metaTitle);
        $hasMetaDescription = ! empty($metaDescription);
        $hasH1 = $html ? (bool) preg_match('/<h1[^>]*>/i', $html) : false;
        $hasH2 = $html ? (bool) preg_match('/<h2[^>]*>/i', $html) : false;
        $headingStructure = $hasH1 && $hasH2 ? 'good' : ($hasH1 ? 'fair' : 'poor');

        $technicalIssues = [];
        $technicalStrengths = [];
        $statusCode = $websiteContent['status_code'] ?? 0;

        if ($statusCode !== 200) {
            $technicalIssues[] = 'Website returned status '.$statusCode;
        }

        if ($websiteContent['has_ssl'] ?? false) {
            $technicalStrengths[] = 'Valid SSL detected';
        } else {
            $technicalIssues[] = 'SSL not detected';
        }

        if ($websiteContent['has_robots'] ?? false) {
            $technicalStrengths[] = 'robots.txt present';
        } else {
            $technicalIssues[] = 'robots.txt missing';
        }

        if ($websiteContent['has_sitemap'] ?? false) {
            $technicalStrengths[] = 'sitemap.xml present';
        } else {
            $technicalIssues[] = 'sitemap.xml missing';
        }

        $contentIssues = [];
        $contentStrengths = [];

        if ($hasMetaTitle) {
            $contentStrengths[] = 'Meta title found';
        } else {
            $contentIssues[] = 'Missing meta title';
        }

        if ($hasMetaDescription) {
            $contentStrengths[] = 'Meta description found';
        } else {
            $contentIssues[] = 'Missing meta description';
        }

        $technicalScore = $this->calculateTechnicalScore($websiteContent);
        $keywordUsage = $this->resolveKeywordUsage($textContent, $input['keywords'] ?? []);
        $contentScore = $this->calculateContentScore($hasMetaTitle, $hasMetaDescription, $headingStructure, $keywordUsage);
        $trustSignals = $this->detectTrustSignals($html, $websiteContent['has_ssl'] ?? null, $input['website_url']);

        $socialPlatforms = $this->detectSocialProfiles($html, $input);
        $socialScore = $this->calculateSocialScore($socialPlatforms);
        $socialIntegrationQuality = $this->determineIntegrationQuality($socialPlatforms);
        $socialRecommendations = $this->buildSocialRecommendations($socialPlatforms);
        $totalPlatforms = $this->countDetectedPlatforms($socialPlatforms);

        $googleBusinessProfile = $this->detectGoogleBusinessProfile($input);
        $googleBusinessScore = $this->calculateLocalPresenceScore($googleBusinessProfile);
        $googleBusinessProfile['score'] = $googleBusinessScore;

        $overallVisibilityScore = $this->calculateOverallGradeScore([
            $technicalScore,
            $contentScore,
            $socialScore ?? 0,
            $googleBusinessScore ?? 0,
        ]);
        $grade = $this->resolveLetterGrade($overallVisibilityScore);

        return [
            'website_audit' => [
                'technical_seo' => [
                    'score' => $technicalScore,
                    'ssl_valid' => $websiteContent['has_ssl'] ?? null,
                    'robots_txt_present' => $websiteContent['has_robots'] ?? null,
                    'sitemap_xml_present' => $websiteContent['has_sitemap'] ?? null,
                    'page_speed_estimate' => [
                        'desktop_ms' => $websiteContent['response_time_ms_desktop'] ?? null,
                        'mobile_ms' => $websiteContent['response_time_ms_mobile'] ?? null,
                    ],
                    'mobile_friendly' => null,
                    'issues' => $technicalIssues,
                    'strengths' => $technicalStrengths,
                ],
                'content_quality' => [
                    'score' => $contentScore,
                    'has_meta_title' => $hasMetaTitle,
                    'has_meta_description' => $hasMetaDescription,
                    'meta_title' => $metaTitle,
                    'meta_description' => $metaDescription,
                    'keyword_usage' => $keywordUsage,
                    'issues' => $contentIssues,
                    'strengths' => $contentStrengths,
                ],
                'security_trust' => [
                    'score' => $trustSignals['score'],
                    'ssl_certificate' => $websiteContent['has_ssl'] ?? null,
                    'privacy_policy_found' => $trustSignals['privacy_policy_found'],
                    'terms_conditions_found' => $trustSignals['terms_conditions_found'],
                    'contact_info_visible' => $trustSignals['contact_info_visible'],
                    'issues' => $trustSignals['issues'],
                ],
            ],
            'social_media_presence' => [
                'business_name' => $input['business_name'] ?? null,
                'website' => $input['website_url'],
                'platforms' => $socialPlatforms,
                'social_score' => $socialScore,
                'total_platforms' => $totalPlatforms,
                'integration_quality' => $socialIntegrationQuality,
                'recommendations' => $socialRecommendations,
            ],
            'google_business_profile' => $googleBusinessProfile,
            'visibility_scores' => [
                'website_audit' => $technicalScore,
                'content_quality' => $contentScore,
                'social_media_presence' => $socialScore ?? 0,
                'google_business_profile' => $googleBusinessScore ?? 0,
                'overall_visibility_score' => $overallVisibilityScore,
                'grade' => $grade,
                'grade_description' => $this->describeGrade($grade),
            ],
            'key_findings' => [
                'strengths' => array_slice(array_merge($technicalStrengths, $contentStrengths), 0, 5),
                'weaknesses' => array_slice(array_merge($technicalIssues, $contentIssues), 0, 5),
                'opportunities' => ['Use SERPER social matches and Google Places data to expand visibility signals'],
                'threats' => [],
            ],
            'recommendations' => [
                'immediate_actions' => [
                    [
                        'priority' => 'medium',
                        'category' => 'technical',
                        'action' => 'Resolve missing robots.txt/sitemap if absent',
                        'impact' => 'medium',
                        'effort' => 'low',
                        'description' => 'Ensure basic crawlability files exist to improve technical SEO.',
                    ],
                ],
                'short_term_strategy' => ['Link SERPER-detected social profiles across the website and verify GBP data.'],
                'long_term_strategy' => ['Decide which verified channels to promote and keep GBP reviews flowing.'],
                'quick_wins' => ['Add meta title and description if missing', 'Increase on-page copy for key pages', 'Add social icons that point to verified profiles'],
            ],
            'competitive_insights' => [
                'market_position_estimate' => 'unknown',
                'differentiation_opportunities' => [],
                'competitive_advantages' => [],
                'areas_for_improvement' => ['Expand Google Business signals and cross-link social profiles'],
            ],
            'website_fetch' => $websiteContent,
        ];
    }

    private function calculateTechnicalScore(array $websiteContent): int
    {
        $score = 0;

        if (($websiteContent['status_code'] ?? 0) === 200) {
            $score += 40;
        }

        if ($websiteContent['has_ssl'] ?? false) {
            $score += 20;
        }

        if ($websiteContent['has_robots'] ?? false) {
            $score += 20;
        }

        if ($websiteContent['has_sitemap'] ?? false) {
            $score += 20;
        }

        return min($score, 100);
    }

    private function calculateContentScore(bool $hasMetaTitle, bool $hasMetaDescription, string $headingStructure, string $keywordUsage): int
    {
        $score = 0;

        if ($hasMetaTitle) {
            $score += 25;
        }

        if ($hasMetaDescription) {
            $score += 25;
        }

        if ($headingStructure === 'good') {
            $score += 20;
        } elseif ($headingStructure === 'fair') {
            $score += 10;
        }

        if ($keywordUsage === 'good') {
            $score += 30;
        } elseif ($keywordUsage === 'fair') {
            $score += 15;
        } elseif ($keywordUsage === 'poor') {
            $score += 0;
        } else { // unknown
            $score += 0;
        }

        return min($score, 100);
    }

    private function measureResponseTime(string $url, string $userAgent): ?float
    {
        try {
            $client = new Client([
                'timeout' => 15,
                'verify' => false,
                'http_errors' => false,
                'headers' => [
                    'User-Agent' => $userAgent,
                ],
            ]);

            $start = microtime(true);
            $client->get($url);
            return round((microtime(true) - $start) * 1000, 1);
        } catch (GuzzleException) {
            return null;
        }
    }

    private function resolvePageSpeed(array $osatChecks, string $preset, ?float $fallback): ?float
    {
        $lh = $osatChecks['lighthouse'][$preset] ?? null;
        if ($lh) {
            if (isset($lh['metrics']['largest_contentful_paint_ms'])) {
                return $lh['metrics']['largest_contentful_paint_ms'];
            }
            if (isset($lh['metrics']['speed_index_ms'])) {
                return $lh['metrics']['speed_index_ms'];
            }
        }

        return $fallback;
    }

    private function resolveMobileFriendly(array $osatChecks, string $htmlPreview): ?bool
    {
        $lhMobile = $osatChecks['lighthouse']['mobile'] ?? null;
        if ($lhMobile) {
            $perf = $lhMobile['scores']['performance'] ?? null;
            if ($perf !== null) {
                return $perf >= 0.5;
            }
        }

        if ($htmlPreview) {
            return (bool) preg_match('/<meta[^>]+name=["\']viewport["\'][^>]*>/i', $htmlPreview);
        }

        return null;
    }

    private function resolveKeywordUsage(string $text, array $keywords): string
    {
        if (empty($keywords)) {
            return 'unknown';
        }

        $textLower = strtolower($text);
        $hits = 0;
        foreach ($keywords as $kw) {
            if (! is_string($kw) || $kw === '') {
                continue;
            }
            $kwLower = strtolower($kw);
            if (str_contains($textLower, $kwLower)) {
                $hits++;
            }
        }

        if ($hits === 0) {
            return 'poor';
        }
        if ($hits <= 2) {
            return 'fair';
        }

        return 'good';
    }

    private function detectSocialProfiles(?string $html, array $input): array
    {
        $results = [];
        $websiteSocials = $this->extractSocialLinksFromHtml($html);
        $tokens = $this->buildSearchTokens($input);

        foreach ($this->socialPlatforms as $platform => $domain) {
            if (! empty($websiteSocials[$platform])) {
                $results[$platform] = [
                    'url' => $websiteSocials[$platform],
                    'source' => 'website',
                    'confidence' => 'HIGH',
                ];
                continue;
            }

            $serperUrl = $this->findPlatformViaSerper($input, $platform, $domain, $tokens);

            if ($serperUrl) {
                $results[$platform] = [
                    'url' => $serperUrl,
                    'source' => 'search',
                    'confidence' => 'LOW',
                ];
                continue;
            }

            $results[$platform] = [
                'url' => 'NOT FOUND',
                'source' => 'none',
                'confidence' => 'NONE',
            ];
        }

        return $results;
    }

    private function extractSocialLinksFromHtml(?string $html): array
    {
        if (! is_string($html) || trim($html) === '') {
            return [];
        }

        preg_match_all(
            '/https?:\\/\\/(www\\.)?(facebook|instagram|linkedin|youtube|tiktok|x|twitter)\\.com\\/[^\\"\\\'<>\\s]+/i',
            $html,
            $matches
        );

        $found = [];

        foreach ($matches[0] as $url) {
            $lower = strtolower($url);
            if (str_contains($lower, 'facebook.com')) {
                $found['facebook'] = $url;
            } elseif (str_contains($lower, 'instagram.com')) {
                $found['instagram'] = $url;
            } elseif (str_contains($lower, 'linkedin.com')) {
                $found['linkedin'] = $url;
            } elseif (str_contains($lower, 'youtube.com')) {
                $found['youtube'] = $url;
            } elseif (str_contains($lower, 'tiktok.com')) {
                $found['tiktok'] = $url;
            } elseif (str_contains($lower, 'x.com') || str_contains($lower, 'twitter.com')) {
                $found['twitter'] = $url;
            }
        }

        return $found;
    }
    
    private function buildSearchTokens(array $input): array
    {
        $tokens = [];
        $sources = [];

        if (! empty($input['business_name']) && is_string($input['business_name'])) {
            $sources[] = $input['business_name'];
        }

        if (! empty($input['description']) && is_string($input['description'])) {
            $sources[] = $input['description'];
        }

        if (! empty($input['city'])) {
            $sources[] = is_array($input['city']) ? implode(' ', array_filter($input['city'])) : (string) $input['city'];
        }

        if (! empty($input['country'])) {
            $sources[] = is_array($input['country']) ? implode(' ', array_filter($input['country'])) : (string) $input['country'];
        }

        if (! empty($input['keywords']) && is_array($input['keywords'])) {
            $sources[] = implode(' ', array_filter($input['keywords'], static fn ($kw) => is_string($kw)));
        }

        foreach ($sources as $text) {
            $tokens = array_merge($tokens, $this->tokenizeText($text));
        }

        return array_values(array_unique(array_filter($tokens)));
    }

    private function tokenizeText(string $text): array
    {
        $text = strtolower($text);
        $text = preg_replace('/\b(ltd|limited|inc|llc|company)\b/', ' ', $text);
        $text = preg_replace('/[^a-z0-9 ]/', ' ', $text);
        $text = trim(preg_replace('/\s+/', ' ', $text));

        if ($text === '') {
            return [];
        }

        $parts = explode(' ', $text);
        $tokens = [];

        foreach ($parts as $part) {
            if (strlen($part) >= 4) {
                $tokens[] = $part;
            }
        }

        if (count($parts) > 1) {
            $tokens[] = implode('', $parts);
        }

        return $tokens;
    }

    private function buildSerperQuery(string $businessName, string $platform, string $domain): string
    {
        return match ($platform) {
            'youtube' => "{$businessName} YouTube channel",
            'twitter' => "{$businessName} X",
            'tiktok' => "{$businessName} TikTok",
            default => "{$businessName} site:{$domain}",
        };
    }

    private function extractSocialUsername(string $url, string $domain): ?string
    {
        $path = trim(parse_url($url, PHP_URL_PATH) ?? '', '/');
        if (! $path) {
            return null;
        }

        $blocked = ['p/', 'reel/', 'tv/', 'watch', 'shorts', 'video'];
        foreach ($blocked as $blockedPattern) {
            if (str_contains($path, $blockedPattern)) {
                return null;
            }
        }

        if ($domain === 'youtube.com') {
            if (str_starts_with($path, '@')) {
                return substr($path, 1);
            }
            if (str_starts_with($path, 'c/')) {
                return substr($path, 2);
            }
            if (str_starts_with($path, 'channel/')) {
                return substr($path, 8);
            }
            return null;
        }

        if ($domain === 'instagram.com') {
            return str_contains($path, '/') ? null : $path;
        }

        if ($domain === 'tiktok.com') {
            return str_starts_with($path, '@') ? substr($path, 1) : null;
        }

        if ($domain === 'linkedin.com') {
            return str_starts_with($path, 'company/') ? substr($path, 8) : null;
        }

        return explode('/', $path)[0];
    }

    private function resolveSerperCountry(array $input): string
    {
        $country = $input['country'] ?? '';
        if (is_array($country)) {
            $country = $country[0] ?? '';
        }

        if (is_string($country) && $country !== '') {
            return $country;
        }

        $countryCode = $input['country_code'] ?? '';
        if (is_string($countryCode) && $countryCode !== '') {
            return $countryCode;
        }

        return 'us';
    }

    private function resolveSerperApiKey(array $input): ?string
    {
        $key = $input['serper_api_key'] ?? $this->serperApiKey;
        if (is_string($key)) {
            $key = trim($key);
        }

        return $key !== '' ? $key : null;
    }

    private function resolveGooglePlacesApiKey(array $input): ?string
    {
        $key = $input['google_places_api_key'] ?? $this->googlePlacesApiKey;
        if (is_string($key)) {
            $key = trim($key);
        }

        return $key !== '' ? $key : null;
    }

    private function findPlatformViaSerper(array $input, string $platform, string $domain, array $tokens): ?string
    {
        $apiKey = $this->resolveSerperApiKey($input);
        if (! $apiKey) {
            return null;
        }

        $businessName = trim($input['business_name'] ?? '');
        if ($businessName === '') {
            return null;
        }

        $query = $this->buildSerperQuery($businessName, $platform, $domain);
        $payload = [
            'q' => $query,
            'gl' => $this->resolveSerperCountry($input),
        ];

        try {
            $response = $this->httpClient->post('https://google.serper.dev/search', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-API-KEY' => $apiKey,
                ],
                'body' => json_encode($payload),
                'timeout' => 20,
            ]);
        } catch (GuzzleException $e) {
            Log::info('Serper search failed', [
                'platform' => $platform,
                'reason' => $e->getMessage(),
            ]);

            return null;
        }

        $data = json_decode((string) $response->getBody(), true);
        if (empty($data['organic'])) {
            return null;
        }

        foreach ($data['organic'] as $result) {
            $url = $result['link'] ?? '';
            if (! is_string($url) || $url === '') {
                continue;
            }

            if ($platform === 'twitter') {
                if (! str_contains($url, 'x.com') && ! str_contains($url, 'twitter.com')) {
                    continue;
                }
                $matchDomain = str_contains($url, 'twitter.com') ? 'twitter.com' : 'x.com';
            } elseif (str_contains($url, $domain)) {
                $matchDomain = $domain;
            } else {
                continue;
            }

            $username = $this->extractSocialUsername($url, $matchDomain);
            if (! $username) {
                continue;
            }

            $username = strtolower($username);
            foreach ($tokens as $token) {
                if ($token !== '' && str_contains($username, $token)) {
                    return $url;
                }
            }
        }

        return null;
    }

    private function detectGoogleBusinessProfile(array $input): array
    {
        $apiKey = $this->resolveGooglePlacesApiKey($input);
        if (! $apiKey) {
            return $this->gbpNotFound('GOOGLE_PLACES_API_KEY missing');
        }

        $business = trim($input['business_name'] ?? '');
        if ($business === '') {
            return $this->gbpNotFound('Business name missing');
        }

        $location = $this->buildLocationQuery($input);
        $query = trim("{$business} {$location}");
        if ($query === '') {
            $query = $business;
        }

        try {
            $response = $this->httpClient->get('https://maps.googleapis.com/maps/api/place/textsearch/json', [
                'query' => [
                    'query' => $query,
                    'key' => $apiKey,
                ],
                'timeout' => 15,
                'headers' => [
                    'Referer' => config('app.url', 'http://localhost'),
                ],
            ]);
        } catch (GuzzleException $e) {
            Log::warning('GBP API Error - Text Search CURL', ['error' => $e->getMessage()]);
            return $this->gbpNotFound('Text Search request failed');
        }

        $data = json_decode((string) $response->getBody(), true);
        if (($data['status'] ?? '') !== 'OK') {
            Log::warning('GBP API Error - Text Search Status', [
                'status' => $data['status'] ?? 'UNKNOWN',
                'query' => $query,
            ]);
            return $this->gbpNotFound('Text Search returned no results');
        }

        $place = $data['results'][0] ?? null;
        if (! $place || empty($place['place_id'])) {
            return $this->gbpNotFound('No Google Business Profile match');
        }

        $placeId = $place['place_id'];

        try {
            $detailsResponse = $this->httpClient->get('https://maps.googleapis.com/maps/api/place/details/json', [
                'query' => [
                    'place_id' => $placeId,
                    'fields' => 'name,formatted_address,formatted_phone_number,rating,user_ratings_total',
                    'key' => $apiKey,
                ],
                'timeout' => 15,
            ]);
        } catch (GuzzleException $e) {
            Log::warning('GBP API Error - Details request failed', ['error' => $e->getMessage(), 'place_id' => $placeId]);
            return $this->gbpNotFound('Details request failed');
        }

        $detailsData = json_decode((string) $detailsResponse->getBody(), true);
        if (($detailsData['status'] ?? '') !== 'OK') {
            Log::warning('GBP API Error - Details Status', [
                'status' => $detailsData['status'] ?? 'UNKNOWN',
                'place_id' => $placeId,
            ]);
            return $this->gbpNotFound('Details lookup failed');
        }

        $details = $detailsData['result'] ?? [];

        $profile = [
            'found' => 'YES',
            'name' => $details['name'] ?? $place['name'] ?? $business,
            'address' => $details['formatted_address'] ?? $place['formatted_address'] ?? 'N/A',
            'phone' => $details['formatted_phone_number'] ?? 'N/A',
            'rating' => $details['rating'] ?? 'N/A',
            'reviews' => $details['user_ratings_total'] ?? 'N/A',
            'confidence' => 'very_high',
        ];

        $tokens = $this->buildSearchTokens($input);
        if (! $this->gbpMatchesTokens($profile, $tokens)) {
            return $this->gbpNotFound('GBP candidate failed keyword verification');
        }

        return $profile;
    }

    private function buildLocationQuery(array $input): string
    {
        $cities = $input['city'] ?? '';
        $countries = $input['country'] ?? '';

        $cityString = is_array($cities) ? implode(' ', array_filter($cities)) : (string) $cities;
        $countryString = is_array($countries) ? implode(' ', array_filter($countries)) : (string) $countries;

        return trim(trim($cityString).' '.trim($countryString));
    }

    private function gbpNotFound(string $reason = ''): array
    {
        if ($reason !== '') {
            Log::info('Google Business Profile lookup skipped', ['reason' => $reason]);
        }

        return [
            'found' => 'NO',
            'name' => 'N/A',
            'address' => 'N/A',
            'phone' => 'N/A',
            'rating' => 'N/A',
            'reviews' => 'N/A',
            'confidence' => 'low',
        ];
    }

    private function gbpMatchesTokens(array $profile, array $tokens): bool
    {
        if (empty($tokens)) {
            return true;
        }

        $haystack = strtolower(($profile['name'] ?? '').' '.($profile['address'] ?? ''));
        if ($haystack === '') {
            return false;
        }

        foreach ($tokens as $token) {
            if ($token !== '' && str_contains($haystack, $token)) {
                return true;
            }
        }

        return false;
    }

    private function calculateLocalPresenceScore(array $gbp): ?int
    {
        if (($gbp['found'] ?? 'NO') !== 'YES') {
            return 0;
        }

        $rating = $gbp['rating'];
        $reviews = $gbp['reviews'];

        $ratingScore = is_numeric($rating) ? (float) $rating : 4.0;
        $ratingScore = ($ratingScore / 5) * 60;

        $reviewScore = 10;
        if (is_numeric($reviews)) {
            $reviewsCount = (int) $reviews;
            if ($reviewsCount >= 50) {
                $reviewScore = 40;
            } elseif ($reviewsCount >= 10) {
                $reviewScore = 25;
            } elseif ($reviewsCount > 0) {
                $reviewScore = 15;
            }
        }

        return (int) round(min(100, $ratingScore + $reviewScore));
    }

    private function calculateSocialScore(array $platforms): ?int
    {
        $rawScore = 0;
        $foundAny = false;
        $maxRawScore = count($this->socialPlatforms) * (12 + 3); // perfect scenario (all linked from website)

        foreach ($platforms as $platform) {
            $source = $platform['source'] ?? 'none';
            if ($source === 'none') {
                continue;
            }

            $foundAny = true;
            $rawScore += 12; // base score per platform found
            $rawScore += $source === 'website' ? 3 : 2; // bonus when linked vs found externally
        }

        if (! $foundAny) {
            return null;
        }

        return (int) round(min(100, ($rawScore / $maxRawScore) * 100));
    }

    private function countDetectedPlatforms(array $platforms): int
    {
        $count = 0;

        foreach ($platforms as $platform) {
            if (
                ($platform['source'] ?? 'none') !== 'none'
                && ! empty($platform['url'])
                && $platform['url'] !== 'NOT FOUND'
            ) {
                $count++;
            }
        }

        return $count;
    }

    private function determineIntegrationQuality(array $platforms): string
    {
        $found = 0;
        $linked = 0;

        foreach ($platforms as $platform) {
            $source = $platform['source'] ?? 'none';
            if ($source === 'none') {
                continue;
            }

            $found++;
            if ($source === 'website') {
                $linked++;
            }
        }

        if ($found === 0) {
            return 'poor';
        }

        $ratio = $linked / $found;

        if ($ratio >= 0.75) {
            return 'excellent';
        }
        if ($ratio >= 0.5) {
            return 'good';
        }
        if ($ratio >= 0.25) {
            return 'fair';
        }

        return 'poor';
    }

    private function buildSocialRecommendations(array $platforms): array
    {
        $recommendations = [];

        foreach ($platforms as $name => $platform) {
            $source = $platform['source'] ?? 'none';
            if ($source === 'none') {
                $recommendations[] = "Claim and optimize your {$name} profile, then add it to your website.";
            } elseif ($source === 'search') {
                $recommendations[] = "Link the {$name} profile from your website to strengthen trust signals.";
            }
        }

        if (empty($recommendations)) {
            $recommendations[] = 'Maintain consistent posting on active social channels.';
        }

        return array_values(array_unique($recommendations));
    }

    private function calculateOverallGradeScore(array $scores): int
    {
        if (empty($scores)) {
            return 0;
        }

        $normalized = array_map(static function ($score) {
            if ($score === null) {
                return 0;
            }

            return max(0, min(100, (int) round($score)));
        }, $scores);

        return (int) round(array_sum($normalized) / count($normalized));
    }

    private function resolveLetterGrade(int $score): string
    {
        return match (true) {
            $score >= 90 => 'A',
            $score >= 80 => 'B',
            $score >= 70 => 'C',
            $score >= 60 => 'D',
            $score >= 50 => 'E',
            default => 'F',
        };
    }

    private function describeGrade(string $grade): string
    {
        return match ($grade) {
            'A' => 'Excellent visibility across all pillars',
            'B' => 'Strong visibility with minor gaps',
            'C' => 'Average visibility with room to grow',
            'D' => 'Below-average visibility; needs attention',
            'E' => 'Weak visibility across channels',
            default => 'Critical visibility gaps detected',
        };
    }

    private function urlExists(string $url): bool
    {
        try {
            $response = $this->httpClient->head($url, ['timeout' => 8]);
            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 400) {
                return true;
            }
        } catch (GuzzleException) {
            // try GET as fallback
        }

        try {
            $response = $this->httpClient->get($url, ['timeout' => 8]);
            return $response->getStatusCode() >= 200 && $response->getStatusCode() < 400;
        } catch (GuzzleException) {
            return false;
        }
    }

    private function extractLinkByKeyword(string $html, array $keywords): ?string
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML($html);
        libxml_clear_errors();
        if (! $loaded) {
            return null;
        }

        $xpath = new \DOMXPath($dom);
        $links = $xpath->query('//a[@href]');
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            if (! $href) {
                continue;
            }
            $text = strtolower(trim($link->textContent));
            $hrefLower = strtolower($href);
            foreach ($keywords as $kw) {
                $kwLower = strtolower($kw);
                if (str_contains($text, $kwLower) || str_contains($hrefLower, $kwLower)) {
                    return $href;
                }
            }
        }

        return null;
    }

    private function resolveRelativeUrl(string $baseUrl, string $href): ?string
    {
        if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
            return $href;
        }

        if (str_starts_with($href, '//')) {
            $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https';
            return $scheme.':'.$href;
        }

        if (str_starts_with($href, '/')) {
            $baseParts = parse_url($baseUrl);
            if (! $baseParts || ! isset($baseParts['scheme'], $baseParts['host'])) {
                return null;
            }
            return $baseParts['scheme'].'://'.$baseParts['host'].$href;
        }

        $baseParts = parse_url($baseUrl);
        if (! $baseParts || ! isset($baseParts['scheme'], $baseParts['host'])) {
            return null;
        }
        $path = isset($baseParts['path']) ? rtrim(dirname($baseParts['path']), '/') : '';
        return $baseParts['scheme'].'://'.$baseParts['host'].$path.'/'.$href;
    }

    private function detectTrustSignals(string $html, ?bool $hasSsl, string $baseUrl): array
    {
        if (! $html) {
            return [
                'score' => null,
                'privacy_policy_found' => null,
                'terms_conditions_found' => null,
                'trust_badges' => null,
                'contact_info_visible' => null,
                'issues' => ['Trust signals not fully evaluated in manual fetch-only mode'],
            ];
        }

        $lower = strtolower($html);

        $privacy = (bool) preg_match('/privacy\s*(policy|notice|statement)/i', $html);
        $terms = (bool) preg_match('/terms\s*(of\s*service|conditions|use)/i', $html);
        if (! $terms && preg_match('/terms/i', $html)) {
            $terms = true;
        }
        $baseUrl = rtrim($baseUrl, '/');

        if (! $privacy) {
            $privacyCandidates = [
                $baseUrl.'/privacy',
                $baseUrl.'/privacy-policy',
                $baseUrl.'/privacy.html',
                $baseUrl.'/privacy-policy.html',
            ];
            foreach ($privacyCandidates as $candidate) {
                if ($this->urlExists($candidate)) {
                    $privacy = true;
                    break;
                }
            }
        }

        if (! $terms) {
            $termsCandidates = [
                $baseUrl.'/terms',
                $baseUrl.'/terms-of-service',
                $baseUrl.'/terms-of-use',
                $baseUrl.'/terms-and-conditions',
                $baseUrl.'/terms-conditions',
                $baseUrl.'/legal/terms',
                $baseUrl.'/legal',
                $baseUrl.'/terms.html',
                $baseUrl.'/terms-of-service.html',
                $baseUrl.'/terms-of-use.html',
                $baseUrl.'/terms-and-conditions.html',
                $baseUrl.'/terms-conditions.html',
            ];
            foreach ($termsCandidates as $candidate) {
                if ($this->urlExists($candidate)) {
                    $terms = true;
                    break;
                }
            }
        }

        if (! $terms) {
            // Try to discover a terms link in the HTML and follow it
            $termLink = $this->extractLinkByKeyword($html, ['terms', 'terms-of-service', 'terms-and-conditions']);
            if ($termLink) {
                $resolved = $this->resolveRelativeUrl($baseUrl, $termLink);
                if ($resolved && $this->urlExists($resolved)) {
                    $terms = true;
                }
            }
        }

        $contactPhone = (bool) preg_match('/\+?\d{1,3}[-.\s]?\(?\d{1,4}\)?[-.\s]?\d{1,4}[-.\s]?\d{1,9}/', $html);
        $contactEmail = (bool) preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $html);
        $contactInfo = $contactPhone || $contactEmail;

        $score = 0;
        $score += ($hasSsl ?? false) ? 25 : 0;
        $score += $privacy ? 25 : 0;
        $score += $terms ? 25 : 0;
        $score += $contactInfo ? 25 : 0;
        $score = $score > 0 ? $score : null;

        $issues = [];
        if (! ($hasSsl ?? false)) {
            $issues[] = 'SSL not detected';
        }
        if (! $privacy) {
            $issues[] = 'Privacy policy not detected in HTML';
        }
        if (! $terms) {
            $issues[] = 'Terms & conditions not detected in HTML';
        }
        if (! $contactInfo) {
            $issues[] = 'Contact info (email/phone) not detected in HTML';
        }

        if (empty($issues)) {
            $issues = [];
        }

        return [
            'score' => $score,
            'privacy_policy_found' => $privacy,
            'terms_conditions_found' => $terms,
            'contact_info_visible' => $contactInfo,
            'issues' => $issues,
        ];
    }

    /**
     * Fallback audit when AI is not available
     */
    private function getFallbackAudit(array $input): array
    {
        return [
            'success' => false,
            'audit_results' => [
                'website_audit' => [
                    'technical_seo' => [
                        'score' => 50,
                        'ssl_valid' => str_starts_with($input['website_url'], 'https://'),
                        'robots_txt_present' => null,
                        'sitemap_xml_present' => null,
                        'page_speed_estimate' => 'unknown',
                        'mobile_friendly' => null,
                        'issues' => ['AI audit unavailable - manual inspection required'],
                        'strengths' => [],
                    ],
                    'content_quality' => [
                        'score' => 50,
                        'has_meta_title' => null,
                        'has_meta_description' => null,
                        'meta_title' => null,
                        'meta_description' => null,
                        'keyword_usage' => 'unknown',
                        'issues' => ['AI audit unavailable'],
                        'strengths' => [],
                    ],
                    'security_trust' => [
                        'score' => 50,
                        'ssl_certificate' => str_starts_with($input['website_url'], 'https://'),
                        'privacy_policy_found' => null,
                        'terms_conditions_found' => null,
                        'trust_badges' => null,
                        'contact_info_visible' => null,
                        'issues' => ['AI audit unavailable'],
                    ],
                ],
                'social_media_presence' => [
                    'business_name' => $input['business_name'],
                    'website' => $input['website_url'],
                    'platforms' => [
                        'facebook' => ['url' => null, 'source' => 'none', 'confidence' => 'NONE'],
                        'instagram' => ['url' => null, 'source' => 'none', 'confidence' => 'NONE'],
                        'twitter' => ['url' => null, 'source' => 'none', 'confidence' => 'NONE'],
                        'linkedin' => ['url' => null, 'source' => 'none', 'confidence' => 'NONE'],
                        'youtube' => ['url' => null, 'source' => 'none', 'confidence' => 'NONE'],
                        'tiktok' => ['url' => null, 'source' => 'none', 'confidence' => 'NONE'],
                    ],
                    'social_score' => 50,
                    'total_platforms' => 0,
                    'integration_quality' => 'unknown',
                    'recommendations' => ['Configure OpenAI API key for social media analysis'],
                ],
                'google_business_profile' => [
                    'found' => 'UNKNOWN',
                    'name' => null,
                    'address' => null,
                    'phone' => null,
                    'rating' => 'N/A',
                    'reviews' => 'N/A',
                    'confidence' => 'low',
                    'score' => 50,
                ],
                'visibility_scores' => [
                    'website_audit' => 50,
                    'content_quality' => 50,
                    'social_media_presence' => 50,
                    'google_business_profile' => 50,
                    'overall_visibility_score' => 50,
                    'grade' => 'E',
                    'grade_description' => $this->describeGrade('E'),
                ],
                'key_findings' => [
                    'strengths' => [],
                    'weaknesses' => ['AI-powered audit requires OpenAI API key'],
                    'opportunities' => ['Enable AI audit for comprehensive analysis'],
                    'threats' => [],
                ],
                'recommendations' => [
                    'immediate_actions' => [
                        [
                            'priority' => 'high',
                            'category' => 'technical',
                            'action' => 'Configure OpenAI API key',
                            'impact' => 'high',
                            'effort' => 'low',
                            'description' => 'Add OPENAI_API_KEY to .env file to enable AI-powered comprehensive audits',
                        ],
                    ],
                    'short_term_strategy' => ['Enable AI audit functionality'],
                    'long_term_strategy' => ['Implement regular AI-powered audits'],
                    'quick_wins' => ['Add OpenAI API key to unlock full audit capabilities'],
                ],
                'competitive_insights' => [
                    'market_position_estimate' => 'unknown',
                    'differentiation_opportunities' => [],
                    'competitive_advantages' => [],
                    'areas_for_improvement' => ['Enable AI audit for competitive analysis'],
                ],
            ],
            'metadata' => [
                'model_used' => 'fallback',
                'audit_method' => 'fallback_mode',
                'note' => 'OpenAI API key not configured. Add OPENAI_API_KEY to .env for AI-powered audits.',
                'timestamp' => now()->toIso8601String(),
            ],
        ];
    }
}
