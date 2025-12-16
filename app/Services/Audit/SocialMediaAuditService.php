<?php

namespace App\Services\Audit;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class SocialMediaAuditService
{
    private Client $client;
    private string $websiteUrl;
    private string $businessName;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'verify' => false,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ]
        ]);
    }

    public function audit(array $input): array
    {
        $this->websiteUrl = rtrim($input['website_url'], '/');
        $this->businessName = $input['business_name'];

        $platforms = [
            'facebook' => $this->detectFacebook(),
            'instagram' => $this->detectInstagram(),
            'twitter_x' => $this->detectTwitter(),
            'linkedin' => $this->detectLinkedIn(),
            'tiktok' => $this->detectTikTok(),
        ];

        return [
            'platforms' => $platforms,
            'total_platforms_detected' => count(array_filter($platforms, fn($p) => $p['present'])),
            'cross_platform_consistency' => $this->checkCrossPlatformConsistency($platforms),
        ];
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

    private function detectFacebook(): array
    {
        $html = $this->fetchPage($this->websiteUrl);
        $result = [
            'present' => false,
            'profile_url' => null,
            'linked_from_website' => false,
            'business_name_match' => null,
            'profile_complete' => null,
        ];

        if (!$html) {
            return $result;
        }

        // Detect Facebook link on website
        if (preg_match('/(?:href|content)=["\']([^"\']*facebook\.com\/[^"\'\/]+)["\']?/i', $html, $match)) {
            $result['present'] = true;
            $result['linked_from_website'] = true;
            $result['profile_url'] = $match[1];
        }

        // Check for Facebook meta tags (og:)
        if (preg_match('/<meta[^>]+property=["\']og:/i', $html)) {
            $result['present'] = true;
        }

        return $result;
    }

    private function detectInstagram(): array
    {
        $html = $this->fetchPage($this->websiteUrl);
        $result = [
            'present' => false,
            'profile_url' => null,
            'linked_from_website' => false,
            'username' => null,
        ];

        if (!$html) {
            return $result;
        }

        // Detect Instagram link on website
        if (preg_match('/(?:href|content)=["\']([^"\']*instagram\.com\/[^"\'\/\?]+)["\']?/i', $html, $match)) {
            $result['present'] = true;
            $result['linked_from_website'] = true;
            $result['profile_url'] = $match[1];

            // Extract username
            if (preg_match('/instagram\.com\/([^\/\?"\']+)/i', $match[1], $usernameMatch)) {
                $result['username'] = $usernameMatch[1];
            }
        }

        return $result;
    }

    private function detectTwitter(): array
    {
        $html = $this->fetchPage($this->websiteUrl);
        $result = [
            'present' => false,
            'profile_url' => null,
            'linked_from_website' => false,
            'handle' => null,
        ];

        if (!$html) {
            return $result;
        }

        // Detect Twitter/X link on website
        if (preg_match('/(?:href|content)=["\']([^"\']*(?:twitter|x)\.com\/[^"\'\/\?]+)["\']?/i', $html, $match)) {
            $result['present'] = true;
            $result['linked_from_website'] = true;
            $result['profile_url'] = $match[1];

            // Extract handle
            if (preg_match('/(?:twitter|x)\.com\/([^\/\?"\']+)/i', $match[1], $handleMatch)) {
                $result['handle'] = '@' . $handleMatch[1];
            }
        }

        // Check for Twitter meta tags
        if (preg_match('/<meta[^>]+name=["\']twitter:/i', $html)) {
            $result['present'] = true;
        }

        return $result;
    }

    private function detectLinkedIn(): array
    {
        $html = $this->fetchPage($this->websiteUrl);
        $result = [
            'present' => false,
            'profile_url' => null,
            'linked_from_website' => false,
            'company_page' => null,
        ];

        if (!$html) {
            return $result;
        }

        // Detect LinkedIn link on website
        if (preg_match('/(?:href|content)=["\']([^"\']*linkedin\.com\/(?:company|in)\/[^"\'\/\?]+)["\']?/i', $html, $match)) {
            $result['present'] = true;
            $result['linked_from_website'] = true;
            $result['profile_url'] = $match[1];
            $result['company_page'] = str_contains($match[1], '/company/');
        }

        return $result;
    }

    private function detectTikTok(): array
    {
        $html = $this->fetchPage($this->websiteUrl);
        $result = [
            'present' => false,
            'profile_url' => null,
            'linked_from_website' => false,
            'username' => null,
        ];

        if (!$html) {
            return $result;
        }

        // Detect TikTok link on website
        if (preg_match('/(?:href|content)=["\']([^"\']*tiktok\.com\/@?[^"\'\/\?]+)["\']?/i', $html, $match)) {
            $result['present'] = true;
            $result['linked_from_website'] = true;
            $result['profile_url'] = $match[1];

            // Extract username
            if (preg_match('/tiktok\.com\/@?([^\/\?"\']+)/i', $match[1], $usernameMatch)) {
                $result['username'] = '@' . ltrim($usernameMatch[1], '@');
            }
        }

        return $result;
    }

    private function checkCrossPlatformConsistency(array $platforms): array
    {
        $presentPlatforms = array_filter($platforms, fn($p) => $p['present']);
        $linkedCount = count(array_filter($presentPlatforms, fn($p) => $p['linked_from_website']));

        return [
            'platforms_with_links' => $linkedCount,
            'all_linked_from_website' => count($presentPlatforms) > 0 && $linkedCount === count($presentPlatforms),
            'consistency_score' => count($presentPlatforms) > 0
                ? round(($linkedCount / count($presentPlatforms)) * 100, 2)
                : 0,
        ];
    }
}
