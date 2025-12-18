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
                            'content' => 'You are an expert digital marketing consultant specializing in online business visibility, SEO, and reputation management. Provide actionable, specific recommendations based on audit data.'
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
        $businessInfo = "Business: {$input['business_name']}\n";
        $businessInfo .= "Industry: {$input['industry']}\n";

        // Handle multiple locations
        $cities = is_array($input['city']) ? implode(', ', $input['city']) : $input['city'];
        $countries = is_array($input['country']) ? implode(', ', $input['country']) : $input['country'];
        $businessInfo .= "Location: {$cities}, {$countries}\n";

        $businessInfo .= "Website: {$input['website_url']}\n";
        $businessInfo .= "Target Audience: {$input['target_audience']}\n";

        if (!empty($input['keywords'])) {
            $businessInfo .= "Keywords: " . implode(', ', $input['keywords']) . "\n";
        }

        if (!empty($input['competitors'])) {
            $businessInfo .= "Competitors: " . implode(', ', $input['competitors']) . "\n";
        }

        $auditSummary = "AUDIT RESULTS:\n\n";
        $auditSummary .= json_encode($auditData, JSON_PRETTY_PRINT);

        $prompt = <<<PROMPT
I need you to analyze the following business and provide comprehensive recommendations for improving their online visibility and reputation.

{$businessInfo}

{$auditSummary}

Please provide detailed, actionable recommendations in the following categories:

1. **SEO Improvements**: Based on the website audit findings, what specific technical SEO, content, and on-page optimizations should be prioritized?

2. **Online Visibility Strategy**: How can this business improve its overall online presence across search engines and relevant platforms?

3. **Social Media Growth**: Based on the social media audit, what platforms should they focus on and what specific actions should they take?

4. **Google Business Profile Optimization**: What steps should they take to maximize their local search visibility?

5. **Content Strategy**: What type of content should they create to attract their target audience and improve rankings?

6. **Competitive Positioning**: If competitors are provided, how can they differentiate and compete effectively?

7. **Quick Wins**: What are the top 3-5 immediate actions they can take this week for maximum impact?

8. **Long-term Strategy**: What should their 3-6 month roadmap look like?

Please be specific, actionable, and prioritize recommendations by impact and effort required.
PROMPT;

        return $prompt;
    }

    /**
     * Fallback recommendations when OpenAI API is not available
     */
    private function getFallbackRecommendations(): array
    {
        return [
            'success' => false,
            'note' => 'OpenAI API key not configured. Using fallback recommendations.',
            'recommendations' => <<<RECOMMENDATIONS
# Business Visibility Audit - Recommendations

## SEO Improvements
- Ensure all images have descriptive alt text for better accessibility and SEO
- Implement a comprehensive internal linking strategy
- Optimize page load speed and Core Web Vitals
- Create unique, compelling meta descriptions for each page
- Fix any broken links identified in the audit

## Online Visibility Strategy
- Claim and optimize all relevant business directory listings
- Implement schema markup for better search engine understanding
- Build high-quality backlinks from industry-relevant sources
- Monitor and manage online reviews across all platforms

## Social Media Growth
- Establish presence on platforms where your target audience is most active
- Create a consistent posting schedule
- Engage with followers and respond to comments promptly
- Share valuable content that addresses your audience's pain points

## Google Business Profile Optimization
- Complete all profile sections with accurate information
- Upload high-quality photos regularly
- Encourage satisfied customers to leave reviews
- Post updates and offers weekly

## Content Strategy
- Create location-specific landing pages
- Publish blog posts addressing common customer questions
- Develop case studies and success stories
- Use target keywords naturally in content

## Quick Wins
1. Add missing meta descriptions and title tags
2. Ensure website has SSL certificate
3. Create and submit sitemap.xml
4. Add social media links to website
5. Optimize Google Business Profile

## Long-term Strategy
- Develop comprehensive content marketing plan
- Build authority through thought leadership content
- Expand to additional marketing channels
- Implement marketing automation
- Regular monitoring and optimization
RECOMMENDATIONS
        ];
    }
}
