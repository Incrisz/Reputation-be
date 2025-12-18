<?php

namespace App\Http\Controllers;

use App\Http\Requests\BusinessAuditRequest;
use App\Services\Audit\AIAuditEngine;
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
    private AIAuditEngine $aiAuditEngine;

    public function __construct(AIAuditEngine $aiAuditEngine)
    {
        $this->aiAuditEngine = $aiAuditEngine;
    }

    #[OA\Post(
        path: '/api/audit/run',
        summary: 'Run comprehensive business visibility audit',
        description: 'Executes a complete audit including: Website SEO analysis, Social media detection, Google Business Profile check, OSAT-style technical probes (Lighthouse/HTTP Observatory/sitemap/internal/keywords), and AI-powered recommendations via OpenAI.',
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
                        oneOf: [
                            new OA\Schema(type: 'string'),
                            new OA\Schema(type: 'array', items: new OA\Items(type: 'string'))
                        ],
                        example: 'United States',
                        description: 'Country where business operates - can be a string or array of countries (required)'
                    ),
                    new OA\Property(
                        property: 'city',
                        oneOf: [
                            new OA\Schema(type: 'string'),
                            new OA\Schema(type: 'array', items: new OA\Items(type: 'string'))
                        ],
                        example: 'San Francisco',
                        description: 'City of operation - can be a string or array of cities (required)'
                    ),
                    new OA\Property(
                        property: 'target_audience',
                        type: 'string',
                        example: 'Small to medium-sized businesses looking to improve their digital presence',
                        description: 'Description of target customer base (required)'
                    ),
                    new OA\Property(
                        property: 'description',
                        type: 'string',
                        nullable: true,
                        example: 'We are a branding-first technology studio focused on Abuja startups.',
                        description: 'Optional description/tell-us-about-your-business text used to validate search matches'
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
                                new OA\Property(
                                    property: 'website_audit',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(
                                            property: 'technical_seo',
                                            type: 'object',
                                            properties: [
                                                new OA\Property(property: 'score', type: 'integer', example: 70),
                                                new OA\Property(property: 'ssl_valid', type: 'boolean', example: true),
                                                new OA\Property(property: 'robots_txt_present', type: 'boolean', example: true),
                                                new OA\Property(property: 'sitemap_xml_present', type: 'boolean', example: true),
                                                new OA\Property(
                                                    property: 'page_speed_estimate',
                                                    type: 'object',
                                                    properties: [
                                                        new OA\Property(property: 'desktop_ms', type: 'number', example: 420.5),
                                                        new OA\Property(property: 'mobile_ms', type: 'number', example: 5792.1),
                                                    ]
                                                ),
                                            ]
                                        ),
                                        new OA\Property(property: 'content_quality', type: 'object'),
                                        new OA\Property(property: 'local_seo', type: 'object'),
                                        new OA\Property(property: 'security_trust', type: 'object'),
                                        new OA\Property(property: 'ux_accessibility', type: 'object'),
                                        new OA\Property(property: 'brand_consistency', type: 'object'),
                                    ]
                                ),
                                new OA\Property(property: 'social_media_presence', type: 'object'),
                                new OA\Property(property: 'google_business_profile', type: 'object'),
                                new OA\Property(
                                    property: 'ai_recommendations',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'content', type: 'string', example: 'Prioritize fixing missing robots.txt and sitemap, add meta descriptions...'),
                                        new OA\Property(property: 'success', type: 'boolean', example: true),
                                        new OA\Property(property: 'model_used', type: 'string', example: 'gpt-4o-mini'),
                                        new OA\Property(property: 'tokens_used', type: 'object', nullable: true),
                                        new OA\Property(property: 'note', type: 'string', nullable: true),
                                        new OA\Property(property: 'error', type: 'string', nullable: true),
                                    ]
                                )
                            ]
                        ),
                        new OA\Property(
                            property: 'metadata',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'model_used', type: 'string', example: 'gpt-4o-mini'),
                                new OA\Property(property: 'audit_method', type: 'string', example: 'manual_fetch_with_osat_probes_and_ai_recommendations'),
                                new OA\Property(property: 'timestamp', type: 'string', example: '2025-12-16T10:44:23+00:00'),
                                new OA\Property(property: 'execution_time', type: 'string', example: '14.54 seconds'),
                                new OA\Property(property: 'note', type: 'string', example: 'OSAT probes added; AI recommendations included; social/GBP web search disabled.')
                            ]
                        ),
                        new OA\Property(
                            property: 'timestamp',
                            type: 'string',
                            example: '2025-12-16T10:44:23+00:00'
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

            $serperApiKey = $this->getSerperApiKey();
            $googlePlacesApiKey = $this->getGooglePlacesApiKey();

            if (! $serperApiKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'SERPER_API_KEY is missing. Add it to your .env file to enable social media detection.',
                ], 500);
            }

            if (! $googlePlacesApiKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'GOOGLE_PLACES_API_KEY is missing. Add it to your .env file to enable Google Business Profile detection.',
                ], 500);
            }

            $input['serper_api_key'] = $serperApiKey;
            $input['google_places_api_key'] = $googlePlacesApiKey;

            // Run AI-powered comprehensive audit
            $auditResponse = $this->aiAuditEngine->runComprehensiveAudit($input);

            // Remove sensitive keys from output
            unset($input['serper_api_key'], $input['google_places_api_key']);

            // Calculate execution time
            $executionTime = round(microtime(true) - $startTime, 2);

            // Return comprehensive AI-powered response
            return response()->json([
                'success' => $auditResponse['success'],
                'message' => $auditResponse['success']
                    ? 'AI-powered audit completed successfully'
                    : 'Audit completed with fallback data (OpenAI API key not configured)',
                'input' => $input,
                'audit_results' => $auditResponse['audit_results'],
                'metadata' => array_merge($auditResponse['metadata'], [
                    'execution_time' => $executionTime . ' seconds',
                ]),
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

    private function getSerperApiKey(): ?string
    {
        $key = config('services.serper.api_key', env('SERPER_API_KEY'));
        if (is_string($key)) {
            $key = trim($key);
        }

        return $key !== '' ? $key : null;
    }

    private function getGooglePlacesApiKey(): ?string
    {
        $key = config('services.google_places.api_key', env('GOOGLE_PLACES_API_KEY'));
        if (is_string($key)) {
            $key = trim($key);
        }

        return $key !== '' ? $key : null;
    }
}
