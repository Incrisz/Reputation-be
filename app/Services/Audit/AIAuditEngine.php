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
    }

    /**
     * Run comprehensive AI-powered audit
     */
    public function runComprehensiveAudit(array $input): array
    {
        // Manual mode: only fetch website content; web searches and AI analysis are disabled
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
        $base = 'Social media and Google Business web searches disabled. OSAT-style probes added (lighthouse/security/extractor/sitemap/internal/keywords). ';

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
     * Search for Google Business Profile on the web
     */
    private function searchGoogleBusiness(array $input): array
    {
        $businessName = $input['business_name'];
        $cities = is_array($input['city']) ? implode(' ', $input['city']) : $input['city'];
        $countries = is_array($input['country']) ? implode(' ', $input['country']) : $input['country'];
        $location = trim($cities.' '.$countries);

        $searchQueries = [
            'google_maps' => $businessName.' '.$location.' google maps',
            'google_business' => $businessName.' '.$location.' google business profile',
            'reviews' => $businessName.' '.$location.' reviews google',
        ];

        $searchResults = [];

        foreach ($searchQueries as $queryType => $query) {
            try {
                $duckDuckGoUrl = 'https://html.duckduckgo.com/html/?q='.urlencode($query);

                $response = $this->httpClient->get($duckDuckGoUrl, [
                    'timeout' => 10,
                    'allow_redirects' => true,
                ]);

                $html = (string) $response->getBody();

                // Check for Google Maps/Business indicators
                $hasGoogleMapsLink = stripos($html, 'google.com/maps') !== false || stripos($html, 'maps.google.com') !== false;
                $hasBusinessLink = stripos($html, 'business.google.com') !== false;
                $hasReviews = stripos($html, 'reviews') !== false && stripos($html, 'google') !== false;

                $searchResults[$queryType] = [
                    'search_performed' => true,
                    'search_query' => $query,
                    'has_google_maps_link' => $hasGoogleMapsLink,
                    'has_business_link' => $hasBusinessLink,
                    'has_reviews_mention' => $hasReviews,
                    'results_html_preview' => substr($html, 0, 2000),
                ];

            } catch (GuzzleException $e) {
                $searchResults[$queryType] = [
                    'search_performed' => false,
                    'error' => 'Search failed: '.$e->getMessage(),
                    'search_query' => $query,
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
    "platforms_detected": {
      "facebook": {
        "present": boolean (ONLY TRUE if found in WEB SEARCH RESULTS, not just website links),
        "url": "extracted URL from search results or null",
        "linked_from_website": boolean,
        "found_in_web_search": boolean,
        "profile_quality_estimate": "high/medium/low/unknown"
      },
      "instagram": {
        "present": boolean (ONLY TRUE if found in WEB SEARCH RESULTS, not just website links),
        "url": "extracted URL from search results or null",
        "linked_from_website": boolean,
        "found_in_web_search": boolean,
        "profile_quality_estimate": "high/medium/low/unknown"
      },
      "twitter": {
        "present": boolean (ONLY TRUE if found in WEB SEARCH RESULTS, not just website links),
        "url": "extracted URL from search results or null",
        "linked_from_website": boolean,
        "found_in_web_search": boolean,
        "profile_quality_estimate": "high/medium/low/unknown"
      },
      "linkedin": {
        "present": boolean (ONLY TRUE if found in WEB SEARCH RESULTS, not just website links),
        "url": "extracted URL from search results or null",
        "linked_from_website": boolean,
        "found_in_web_search": boolean,
        "profile_quality_estimate": "high/medium/low/unknown"
      },
      "tiktok": {
        "present": boolean (ONLY TRUE if found in WEB SEARCH RESULTS, not just website links),
        "url": "extracted URL from search results or null",
        "linked_from_website": boolean,
        "found_in_web_search": boolean,
        "profile_quality_estimate": "high/medium/low/unknown"
      }
    },
    "social_score": 0-100,
    "total_platforms": number (count ONLY platforms where found_in_web_search is true),
    "integration_quality": "excellent/good/fair/poor",
    "recommendations": ["array of social media recommendations"]
  },
  "google_business_profile": {
    "likely_has_profile": boolean,
    "confidence_level": "high/medium/low",
    "profile_completeness_estimate": 0-100,
    "signals": {
      "business_type_suitable": boolean,
      "location_specific": boolean,
      "contact_info_available": boolean,
      "reviews_mentioned": boolean
    },
    "recommendations": ["array of GBP recommendations"]
  },
  "visibility_scores": {
    "website_score": 0-100,
    "social_media_score": 0-100,
    "local_presence_score": 0-100,
    "overall_visibility_score": 0-100,
    "grade": "A+/A/B/C/D/F",
    "grade_description": "Excellent/Good/Above Average/Average/Below Average/Poor"
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
        $websiteScore = round(($technicalScore + $contentScore) / 2);
        $trustSignals = $this->detectTrustSignals($html, $websiteContent['has_ssl'] ?? null, $input['website_url']);

        $socialPlatforms = [
            'facebook' => $this->blankPlatformResult(),
            'instagram' => $this->blankPlatformResult(),
            'twitter' => $this->blankPlatformResult(),
            'linkedin' => $this->blankPlatformResult(),
            'tiktok' => $this->blankPlatformResult(),
        ];

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
                'platforms_detected' => $socialPlatforms,
                'social_score' => null,
                'total_platforms' => 0,
                'integration_quality' => 'not_checked',
                'recommendations' => ['Social media detection skipped; enable checks after manual review'],
            ],
            'google_business_profile' => [
                'likely_has_profile' => null,
                'confidence_level' => 'not_checked',
                'profile_completeness_estimate' => null,
                'signals' => [
                    'business_type_suitable' => null,
                    'location_specific' => null,
                    'contact_info_available' => null,
                    'reviews_mentioned' => null,
                ],
                'recommendations' => ['Google Business Profile search disabled; enable later for full check'],
            ],
            'visibility_scores' => [
                'website_score' => $websiteScore,
                'social_media_score' => null,
                'local_presence_score' => null,
                'overall_visibility_score' => $websiteScore,
                'grade' => 'N/A',
                'grade_description' => 'Manual fetch-only audit; social and local checks not rated',
            ],
            'key_findings' => [
                'strengths' => array_slice(array_merge($technicalStrengths, $contentStrengths), 0, 5),
                'weaknesses' => array_slice(array_merge($technicalIssues, $contentIssues), 0, 5),
                'opportunities' => ['Re-enable social/GBP checks for deeper insights'],
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
                'short_term_strategy' => ['Run full social and Google Business checks after manual review'],
                'long_term_strategy' => ['Decide which data to feed into AI for richer scoring'],
                'quick_wins' => ['Add meta title and description if missing', 'Increase on-page copy for key pages'],
            ],
            'competitive_insights' => [
                'market_position_estimate' => 'unknown',
                'differentiation_opportunities' => [],
                'competitive_advantages' => [],
                'areas_for_improvement' => ['Awaiting full audit once social/GBP checks enabled'],
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

    private function blankPlatformResult(): array
    {
        return [
            'present' => null,
            'url' => null,
            'linked_from_website' => false,
            'found_in_web_search' => false,
            'profile_quality_estimate' => 'not_checked',
        ];
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
                $baseUrl.'/terms-and-conditions',
                $baseUrl.'/terms.html',
                $baseUrl.'/terms-of-service.html',
                $baseUrl.'/terms-and-conditions.html',
            ];
            foreach ($termsCandidates as $candidate) {
                if ($this->urlExists($candidate)) {
                    $terms = true;
                    break;
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
                    'platforms_detected' => [
                        'facebook' => ['present' => null, 'url' => null, 'linked_from_website' => false, 'profile_quality_estimate' => 'unknown'],
                        'instagram' => ['present' => null, 'url' => null, 'linked_from_website' => false, 'profile_quality_estimate' => 'unknown'],
                        'twitter' => ['present' => null, 'url' => null, 'linked_from_website' => false, 'profile_quality_estimate' => 'unknown'],
                        'linkedin' => ['present' => null, 'url' => null, 'linked_from_website' => false, 'profile_quality_estimate' => 'unknown'],
                        'tiktok' => ['present' => null, 'url' => null, 'linked_from_website' => false, 'profile_quality_estimate' => 'unknown'],
                    ],
                    'social_score' => 50,
                    'total_platforms' => 0,
                    'integration_quality' => 'unknown',
                    'recommendations' => ['Configure OpenAI API key for social media analysis'],
                ],
                'google_business_profile' => [
                    'likely_has_profile' => null,
                    'confidence_level' => 'unknown',
                    'profile_completeness_estimate' => 50,
                    'signals' => [
                        'business_type_suitable' => null,
                        'location_specific' => true,
                        'contact_info_available' => null,
                        'reviews_mentioned' => null,
                    ],
                    'recommendations' => ['Configure OpenAI API key for GBP analysis'],
                ],
                'visibility_scores' => [
                    'website_score' => 50,
                    'social_media_score' => 50,
                    'local_presence_score' => 50,
                    'overall_visibility_score' => 50,
                    'grade' => 'D',
                    'grade_description' => 'Below Average - AI audit unavailable',
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
