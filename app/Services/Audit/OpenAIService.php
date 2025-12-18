<?php

namespace App\Services\Audit;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    private Client $client;
    private string $apiKey;
    private string $model;
    private string $provider;

    public function __construct()
    {
        $openAiConfig = config('services.openai', []);
        $this->provider = strtolower($openAiConfig['provider'] ?? env('LLM_PROVIDER', 'openai'));

        if ($this->provider === 'openrouter') {
            $routerConfig = config('services.openrouter', []);
            $this->apiKey = $routerConfig['api_key'] ?? env('OPENROUTER_API_KEY', '');
            $this->model = $routerConfig['model'] ?? env('OPENROUTER_MODEL', 'openrouter/auto');
            $baseUri = rtrim($routerConfig['base_url'] ?? env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1/'), '/') . '/';

            $headers = [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ];

            $referer = $routerConfig['site_url'] ?? env('OPENROUTER_SITE_URL');
            if (! empty($referer)) {
                $headers['HTTP-Referer'] = $referer;
            }

            $title = $routerConfig['app_title'] ?? env('OPENROUTER_APP_TITLE');
            if (! empty($title)) {
                $headers['X-Title'] = $title;
            }
        } else {
            $this->provider = 'openai';
            $this->apiKey = $openAiConfig['api_key'] ?? env('OPENAI_API_KEY', '');
            $this->model = $openAiConfig['model'] ?? env('OPENAI_MODEL', 'gpt-4o-mini');
            $baseUri = rtrim($openAiConfig['base_url'] ?? env('OPENAI_BASE_URL', 'https://api.openai.com/v1/'), '/') . '/';

            $headers = [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ];
        }

        $this->client = new Client([
            'base_uri' => $baseUri,
            'timeout' => 120,
            'headers' => $headers,
        ]);
    }

    /**
     * Generate AI recommendations based on audit results
     */
    public function generateRecommendations(array $auditData, array $input): array
    {
        if (empty($this->apiKey)) {
            return $this->getFallbackRecommendations();
        }

        $prompt = $this->buildPrompt($auditData, $input);

        try {
            $response = $this->client->post('chat/completions', [
                'json' => [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a strict verification assistant. Only determine whether social media or Google Business listings belong to the provided business. Use short verdicts like "Instagram is verified via website" or "TikTok page does not belong to this business." Never mention SEO, HTML, or other data.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 2000,
                ]
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            return [
                'success' => true,
                'recommendations' => $body['choices'][0]['message']['content'] ?? 'No recommendations generated',
                'model_used' => $this->model,
                'tokens_used' => $body['usage'] ?? null,
            ];

        } catch (GuzzleException $e) {
            Log::error('OpenAI API Error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => 'Failed to generate AI recommendations: ' . $e->getMessage(),
                'recommendations' => $this->getFallbackRecommendations()['recommendations'],
            ];
        }
    }

    /**
     * Build comprehensive prompt for GPT-4
     */
    private function buildPrompt(array $auditData, array $input): string
    {
        $businessName = $input['business_name'] ?? 'Unknown Business';
        $website = $input['website_url'] ?? 'N/A';
        $domain = parse_url($website, PHP_URL_HOST) ?: $website;
        $description = $input['description'] ?? 'Not provided';
        $cities = is_array($input['city'] ?? null) ? implode(', ', $input['city']) : ($input['city'] ?? 'N/A');
        $countries = is_array($input['country'] ?? null) ? implode(', ', $input['country']) : ($input['country'] ?? 'N/A');

        $platforms = $auditData['social_media_presence']['platforms'] ?? [];
        $trusted = [];
        $unverified = [];

        foreach ($platforms as $name => $platform) {
            $url = $platform['url'] ?? null;
            if (! $url || $url === 'NOT FOUND' || $url === 'NOT VERIFIED') {
                continue;
            }

            $source = $platform['source'] ?? 'none';
            $status = $platform['verification_status'] ?? 'not_found';
            $notes = $platform['verification_notes'] ?? '';

            if ($source === 'website') {
                $trusted[] = strtoupper($name).": {$url}";
            } else {
                $unverified[] = [
                    'platform' => strtoupper($name),
                    'url' => $url,
                    'status' => $status,
                    'notes' => $notes,
                ];
            }
        }

        $gbp = $auditData['google_business_profile'] ?? [];
        $gbpCandidate = null;
        if (($gbp['found'] ?? 'NO') === 'YES' && ! empty($gbp['name'])) {
            $gbpCandidate = [
                'name' => $gbp['name'],
                'address' => $gbp['address'] ?? 'N/A',
                'phone' => $gbp['phone'] ?? 'N/A',
                'rating' => $gbp['rating'] ?? 'N/A',
                'reviews' => $gbp['reviews'] ?? 'N/A',
                'status' => $gbp['verification_status'] ?? 'unknown',
                'notes' => $gbp['verification_notes'] ?? '',
            ];
        }

        $trustedBlock = empty($trusted) ? "- None\n" : implode("\n", array_map(fn ($line) => "- {$line}", $trusted))."\n";

        $unverifiedBlock = "- None\n";
        if (! empty($unverified)) {
            $lines = [];
            foreach ($unverified as $candidate) {
                $notes = $candidate['notes'] ? " | Notes: {$candidate['notes']}" : '';
                $lines[] = "- {$candidate['platform']}: {$candidate['url']} | Status: {$candidate['status']}{$notes}";
            }
            $unverifiedBlock = implode("\n", $lines)."\n";
        }

        $gbpBlock = "- None\n";
        if ($gbpCandidate) {
            $notes = $gbpCandidate['notes'] ? " | Notes: {$gbpCandidate['notes']}" : '';
            $gbpBlock = "- {$gbpCandidate['name']} ({$gbpCandidate['address']}) | Phone: {$gbpCandidate['phone']} | Rating: {$gbpCandidate['rating']} ({$gbpCandidate['reviews']} reviews) | Status: {$gbpCandidate['status']}{$notes}\n";
        }

        return <<<PROMPT
You verify whether discovered social profiles and Google Business listings truly belong to a business.

Business:
- Name: {$businessName}
- Domain: {$domain}
- Description: {$description}
- Location: {$cities}, {$countries}

Trusted (linked from website – already verified):
{$trustedBlock}

Unverified social/GBP candidates:
{$unverifiedBlock}

Google Business Profile candidate:
{$gbpBlock}

Rules:
1. Profiles linked from the official website are VERIFIED automatically.
2. For every other record, compare the business name, description, domain, and location before deciding.
3. If the name or location clearly does not match, respond with "NOT OWNED".
4. If there is no confident match, respond with "NOT FOUND".
5. NEVER assume ownership without evidence.

Output:
- Provide one short verdict per record (e.g., "Instagram is verified via website", "TikTok page does not belong to this business", "No Google Business Profile exists").
- No explanations unless explicitly requested.
- Ignore all technical/SEO/performance data.
PROMPT;
    }

    /**
     * Fallback recommendations when OpenAI API is not available
     */
    private function getFallbackRecommendations(): array
    {
        return [
            'success' => false,
            'note' => 'AI verification unavailable. Using fallback verdicts.',
            'recommendations' => <<<TEXT
AI verification temporarily unavailable.
- Facebook: NOT CHECKED
- Instagram: NOT CHECKED
- Twitter: NOT CHECKED
- LinkedIn: NOT CHECKED
- YouTube: NOT CHECKED
- TikTok: NOT CHECKED
- Google Business Profile: NOT CHECKED
TEXT
        ];
    }
}
