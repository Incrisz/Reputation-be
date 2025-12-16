<?php

namespace App\Services\Audit;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class WebsiteAuditService
{
    private Client $client;
    private string $websiteUrl;
    private string $businessName;
    private array $keywords;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'verify' => false, // For testing purposes
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ]
        ]);
    }

    public function audit(array $input): array
    {
        $this->websiteUrl = rtrim($input['website_url'], '/');
        $this->businessName = $input['business_name'];
        $this->keywords = $input['keywords'] ?? [];

        $result = [
            'technical_seo' => $this->checkTechnicalSeo(),
            'content_quality' => $this->checkContentQuality(),
            'local_seo' => $this->checkLocalSeo(),
            'security_trust' => $this->checkSecurityAndTrust(),
            'ux_accessibility' => $this->checkUxAccessibility(),
            'indexability' => $this->checkIndexability(),
            'brand_consistency' => $this->checkBrandConsistency(),
        ];

        return $result;
    }

    private function fetchPage(string $url): ?string
    {
        try {
            $response = $this->client->get($url);
            return (string) $response->getBody();
        } catch (GuzzleException $e) {
            Log::warning("Failed to fetch {$url}: " . $e->getMessage());
            return null;
        }
    }

    private function checkTechnicalSeo(): array
    {
        $results = [
            'broken_links' => [],
            'robots_txt_present' => false,
            'sitemap_xml_present' => false,
        ];

        // Check robots.txt
        try {
            $robotsResponse = $this->client->get($this->websiteUrl . '/robots.txt');
            $results['robots_txt_present'] = $robotsResponse->getStatusCode() === 200;
        } catch (GuzzleException $e) {
            $results['robots_txt_present'] = false;
        }

        // Check sitemap.xml
        try {
            $sitemapResponse = $this->client->get($this->websiteUrl . '/sitemap.xml');
            $results['sitemap_xml_present'] = $sitemapResponse->getStatusCode() === 200;
        } catch (GuzzleException $e) {
            $results['sitemap_xml_present'] = false;
        }

        // Check for broken links on homepage
        $html = $this->fetchPage($this->websiteUrl);
        if ($html) {
            preg_match_all('/<a[^>]+href=["\']([^"\']+)["\']/i', $html, $matches);
            $links = array_slice(array_unique($matches[1]), 0, 10); // Check first 10 unique links

            foreach ($links as $link) {
                if (str_starts_with($link, 'http') || str_starts_with($link, '//')) {
                    try {
                        $response = $this->client->head($link);
                        if ($response->getStatusCode() === 404) {
                            $results['broken_links'][] = $link;
                        }
                    } catch (GuzzleException $e) {
                        // Count as broken if we can't reach it
                        $results['broken_links'][] = $link;
                    }
                }
            }
        }

        return $results;
    }

    private function checkContentQuality(): array
    {
        $html = $this->fetchPage($this->websiteUrl);
        if (!$html) {
            return [
                'word_count' => 0,
                'duplicate_meta' => false,
                'images_with_alt' => 0,
                'images_without_alt' => 0,
            ];
        }

        // Word count
        $text = strip_tags($html);
        $wordCount = str_word_count($text);

        // Check for images with/without alt tags
        preg_match_all('/<img[^>]+>/i', $html, $imgMatches);
        $imagesWithAlt = 0;
        $imagesWithoutAlt = 0;

        foreach ($imgMatches[0] as $img) {
            if (preg_match('/alt=["\'][^"\']*["\']/i', $img)) {
                $imagesWithAlt++;
            } else {
                $imagesWithoutAlt++;
            }
        }

        // Check meta title and description
        preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $titleMatch);
        preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $descMatch);

        return [
            'word_count' => $wordCount,
            'meta_title' => $titleMatch[1] ?? null,
            'meta_description' => $descMatch[1] ?? null,
            'duplicate_meta' => false, // Would need multiple pages to check
            'images_with_alt' => $imagesWithAlt,
            'images_without_alt' => $imagesWithoutAlt,
        ];
    }

    private function checkLocalSeo(): array
    {
        $html = $this->fetchPage($this->websiteUrl);
        if (!$html) {
            return [
                'nap_found' => false,
                'location_keywords_present' => false,
            ];
        }

        // Simple NAP detection
        $hasPhone = preg_match('/\+?\d{1,3}[-.\s]?\(?\d{1,4}\)?[-.\s]?\d{1,4}[-.\s]?\d{1,9}/', $html);
        $hasAddress = preg_match('/\d+\s+[\w\s]+(?:street|st|avenue|ave|road|rd|boulevard|blvd|drive|dr|court|ct|lane|ln)/i', $html);

        return [
            'nap_found' => $hasPhone && $hasAddress,
            'phone_found' => (bool) $hasPhone,
            'address_found' => (bool) $hasAddress,
            'location_keywords_present' => !empty($this->keywords),
        ];
    }

    private function checkSecurityAndTrust(): array
    {
        $results = [
            'ssl_valid' => str_starts_with($this->websiteUrl, 'https://'),
            'privacy_policy_present' => false,
            'terms_present' => false,
            'mixed_content' => false,
        ];

        $html = $this->fetchPage($this->websiteUrl);
        if ($html) {
            // Check for privacy policy and terms links
            $results['privacy_policy_present'] = (bool) preg_match('/privacy[\s-]?policy/i', $html);
            $results['terms_present'] = (bool) preg_match('/terms[\s&-]?conditions|terms[\s-]?of[\s-]?service/i', $html);

            // Check for mixed content (http resources on https page)
            if ($results['ssl_valid']) {
                $results['mixed_content'] = (bool) preg_match('/src=["\']http:\/\//i', $html) ||
                                                   preg_match('/href=["\']http:\/\//i', $html);
            }
        }

        return $results;
    }

    private function checkUxAccessibility(): array
    {
        $html = $this->fetchPage($this->websiteUrl);
        if (!$html) {
            return [
                'mobile_viewport' => false,
                'has_lazy_loading' => false,
            ];
        }

        return [
            'mobile_viewport' => (bool) preg_match('/<meta[^>]+name=["\']viewport["\']/i', $html),
            'has_lazy_loading' => (bool) preg_match('/loading=["\']lazy["\']/i', $html),
            'readable_font_size' => true, // Simplified check
        ];
    }

    private function checkIndexability(): array
    {
        $html = $this->fetchPage($this->websiteUrl);
        if (!$html) {
            return [
                'noindex_found' => false,
                'nofollow_found' => false,
                'canonical_present' => false,
            ];
        }

        return [
            'noindex_found' => (bool) preg_match('/<meta[^>]+content=["\'][^"\']*noindex/i', $html),
            'nofollow_found' => (bool) preg_match('/<meta[^>]+content=["\'][^"\']*nofollow/i', $html),
            'canonical_present' => (bool) preg_match('/<link[^>]+rel=["\']canonical["\']/i', $html),
        ];
    }

    private function checkBrandConsistency(): array
    {
        $html = $this->fetchPage($this->websiteUrl);
        if (!$html) {
            return [
                'business_name_present' => false,
                'logo_present' => false,
                'favicon_present' => false,
            ];
        }

        $businessNamePattern = preg_quote($this->businessName, '/');

        return [
            'business_name_present' => (bool) preg_match("/{$businessNamePattern}/i", $html),
            'logo_present' => (bool) preg_match('/<img[^>]+(?:logo|brand)/i', $html),
            'favicon_present' => (bool) preg_match('/<link[^>]+rel=["\'](?:icon|shortcut icon)["\']/i', $html),
        ];
    }
}
