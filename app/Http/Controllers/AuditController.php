<?php

namespace App\Http\Controllers;

use App\Http\Requests\BusinessAuditRequest;
use App\Services\Audit\WebsiteAuditService;
use App\Services\Audit\SocialMediaAuditService;
use App\Services\Audit\GoogleBusinessAuditService;
use App\Services\Audit\OpenAIService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Business Reputation & Visibility Audit API',
    description: 'A comprehensive API for auditing business online presence, SEO, social media, and Google Business Profile. Provides AI-powered recommendations via GPT-4.',
)]
#[OA\Server(
    url: 'http://localhost:8000',
    description: 'Local development server'
)]
#[OA\Server(
    url: 'http://localhost:8000/api',
    description: 'API base path'
)]
class AuditController extends Controller
{
    private WebsiteAuditService $websiteAudit;
    private SocialMediaAuditService $socialMediaAudit;
    private GoogleBusinessAuditService $googleBusinessAudit;
    private OpenAIService $openAIService;

    public function __construct(
        WebsiteAuditService $websiteAudit,
        SocialMediaAuditService $socialMediaAudit,
        GoogleBusinessAuditService $googleBusinessAudit,
        OpenAIService $openAIService
    ) {
        $this->websiteAudit = $websiteAudit;
        $this->socialMediaAudit = $socialMediaAudit;
        $this->googleBusinessAudit = $googleBusinessAudit;
        $this->openAIService = $openAIService;
    }

    #[OA\Post(
        path: '/api/audit/run',
        summary: 'Run comprehensive business visibility audit',
        description: 'Executes a complete audit including: Website SEO analysis, Social media detection, Google Business Profile check, and generates AI-powered recommendations via GPT-4.',
        tags: ['Audit'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['website_url', 'business_name', 'industry', 'country', 'city', 'target_audience'],
                properties: [
                    new OA\Property(
                        property: 'website_url',
                        type: 'string',
                        format: 'url',
                        example: 'https://example.com',
                        description: 'Full URL of the business website (required)'
                    ),
                    new OA\Property(
                        property: 'business_name',
                        type: 'string',
                        example: 'Acme Digital Solutions',
                        description: 'Official business name (required)'
                    ),
                    new OA\Property(
                        property: 'industry',
                        type: 'string',
                        example: 'Digital Marketing',
                        description: 'Business industry or category (required)'
                    ),
                    new OA\Property(
                        property: 'country',
                        type: 'string',
                        example: 'United States',
                        description: 'Country where business operates (required)'
                    ),
                    new OA\Property(
                        property: 'city',
                        type: 'string',
                        example: 'San Francisco',
                        description: 'Primary city of operation (required)'
                    ),
                    new OA\Property(
                        property: 'target_audience',
                        type: 'string',
                        example: 'Small to medium-sized businesses looking to improve their digital presence',
                        description: 'Description of target customer base (required)'
                    ),
                    new OA\Property(
                        property: 'competitors',
                        type: 'array',
                        items: new OA\Items(type: 'string'),
                        example: ['https://competitor1.com', 'https://competitor2.com'],
                        description: 'Optional list of competitor websites'
                    ),
                    new OA\Property(
                        property: 'keywords',
                        type: 'array',
                        items: new OA\Items(type: 'string'),
                        example: ['digital marketing', 'SEO services', 'social media management'],
                        description: 'Optional list of target keywords'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Audit completed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'success',
                            type: 'boolean',
                            example: true
                        ),
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Audit completed successfully'
                        ),
                        new OA\Property(
                            property: 'input',
                            type: 'object',
                            description: 'Echo of input parameters'
                        ),
                        new OA\Property(
                            property: 'audit_results',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'website_audit', type: 'object'),
                                new OA\Property(property: 'social_media_presence', type: 'object'),
                                new OA\Property(property: 'google_business_profile', type: 'object'),
                            ]
                        ),
                        new OA\Property(
                            property: 'scores',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'website_score', type: 'integer', example: 75),
                                new OA\Property(property: 'social_media_score', type: 'integer', example: 60),
                                new OA\Property(property: 'overall_score', type: 'integer', example: 68),
                            ]
                        ),
                        new OA\Property(
                            property: 'ai_recommendations',
                            type: 'object',
                            description: 'GPT-4 generated recommendations and insights'
                        ),
                        new OA\Property(
                            property: 'execution_time',
                            type: 'string',
                            example: '15.32 seconds'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Validation failed'),
                        new OA\Property(property: 'errors', type: 'object'),
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Internal server error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Audit failed'),
                        new OA\Property(property: 'error', type: 'string'),
                    ]
                )
            ),
        ]
    )]
    public function run(BusinessAuditRequest $request): JsonResponse
    {
        $startTime = microtime(true);

        try {
            // Get validated input
            $input = $request->validated();

            // Step 1: Run Website Audit
            $websiteAuditResults = $this->websiteAudit->audit($input);

            // Step 2: Run Social Media Detection
            $socialMediaResults = $this->socialMediaAudit->audit($input);

            // Step 3: Run Google Business Profile Detection
            $googleBusinessResults = $this->googleBusinessAudit->audit($input);

            // Step 4: Aggregate structured data
            $auditResults = [
                'website_audit' => $websiteAuditResults,
                'social_media_presence' => $socialMediaResults,
                'google_business_profile' => $googleBusinessResults,
            ];

            // Step 5: Calculate scores
            $scores = $this->calculateScores($auditResults);

            // Step 6: Send to GPT-4 for AI recommendations
            $aiRecommendations = $this->openAIService->generateRecommendations($auditResults, $input);

            // Calculate execution time
            $executionTime = round(microtime(true) - $startTime, 2);

            // Step 7: Return comprehensive response
            return response()->json([
                'success' => true,
                'message' => 'Audit completed successfully',
                'input' => $input,
                'audit_results' => $auditResults,
                'scores' => $scores,
                'ai_recommendations' => $aiRecommendations,
                'execution_time' => $executionTime . ' seconds',
                'timestamp' => now()->toIso8601String(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Audit failed',
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }

    /**
     * Calculate scores based on audit results
     */
    private function calculateScores(array $auditResults): array
    {
        $websiteScore = $this->calculateWebsiteScore($auditResults['website_audit']);
        $socialMediaScore = $this->calculateSocialMediaScore($auditResults['social_media_presence']);
        $googleBusinessScore = $this->calculateGoogleBusinessScore($auditResults['google_business_profile']);

        $overallScore = round(($websiteScore + $socialMediaScore + $googleBusinessScore) / 3);

        return [
            'website_score' => $websiteScore,
            'social_media_score' => $socialMediaScore,
            'google_business_score' => $googleBusinessScore,
            'overall_score' => $overallScore,
            'grade' => $this->getGrade($overallScore),
        ];
    }

    private function calculateWebsiteScore(array $websiteAudit): int
    {
        $score = 0;
        $maxScore = 100;

        // Technical SEO (20 points)
        if ($websiteAudit['technical_seo']['robots_txt_present']) $score += 7;
        if ($websiteAudit['technical_seo']['sitemap_xml_present']) $score += 7;
        if (count($websiteAudit['technical_seo']['broken_links']) === 0) $score += 6;

        // Content Quality (20 points)
        if ($websiteAudit['content_quality']['word_count'] > 300) $score += 10;
        if ($websiteAudit['content_quality']['images_with_alt'] > 0) $score += 10;

        // Security & Trust (20 points)
        if ($websiteAudit['security_trust']['ssl_valid']) $score += 10;
        if ($websiteAudit['security_trust']['privacy_policy_present']) $score += 5;
        if ($websiteAudit['security_trust']['terms_present']) $score += 5;

        // UX & Accessibility (15 points)
        if ($websiteAudit['ux_accessibility']['mobile_viewport']) $score += 10;
        if ($websiteAudit['ux_accessibility']['has_lazy_loading']) $score += 5;

        // Indexability (15 points)
        if (!$websiteAudit['indexability']['noindex_found']) $score += 8;
        if ($websiteAudit['indexability']['canonical_present']) $score += 7;

        // Brand Consistency (10 points)
        if ($websiteAudit['brand_consistency']['business_name_present']) $score += 4;
        if ($websiteAudit['brand_consistency']['logo_present']) $score += 3;
        if ($websiteAudit['brand_consistency']['favicon_present']) $score += 3;

        return min($score, $maxScore);
    }

    private function calculateSocialMediaScore(array $socialMedia): int
    {
        $totalPlatforms = $socialMedia['total_platforms_detected'];
        $consistencyScore = $socialMedia['cross_platform_consistency']['consistency_score'];

        // Base score on number of platforms (max 70 points)
        $platformScore = min($totalPlatforms * 14, 70);

        // Consistency adds up to 30 points
        $consistencyPoints = round($consistencyScore * 0.3);

        return min($platformScore + $consistencyPoints, 100);
    }

    private function calculateGoogleBusinessScore(array $googleBusiness): int
    {
        // Since Google Business detection is simulated, return neutral score
        // In production, this would analyze actual GBP data
        return 50; // Neutral score indicating "needs verification"
    }

    private function getGrade(int $score): string
    {
        return match (true) {
            $score >= 90 => 'A+ Excellent',
            $score >= 80 => 'A Good',
            $score >= 70 => 'B Above Average',
            $score >= 60 => 'C Average',
            $score >= 50 => 'D Below Average',
            default => 'F Poor',
        };
    }
}
