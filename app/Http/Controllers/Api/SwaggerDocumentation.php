<?php

namespace App\Http\Controllers\Api;

/**
 * @OA\Info(
 *    title="BizVisibility AI - Website Audit & Reputation API",
 *    version="1.0.0",
 *    description="Complete API for website audits, social media detection, SEO analysis, and AI-powered recommendations. Multi-tenant SaaS platform for business visibility and reputation management.",
 *    contact={
 *       "name": "Support Team",
 *       "email": "support@bizvisibility.ai",
 *       "url": "https://bizvisibility.ai"
 *    },
 *    license={
 *       "name": "Proprietary",
 *       "url": "https://bizvisibility.ai/license"
 *    }
 * )
 * @OA\Server(
 *    url=L5_SWAGGER_CONST_HOST,
 *    description="API Server"
 * )
 * @OA\SecurityScheme(
 *    type="http",
 *    description="Login with email and password to get the authentication token",
 *    name="Token based security",
 *    in="header",
 *    scheme="bearer",
 *    bearerFormat="Sanctum",
 *    securityScheme="bearerToken"
 * )
 */

/**
 * ==============================================================
 * AUTHENTICATION ENDPOINTS
 * ==============================================================
 */

/**
 * @OA\Post(
 *     path="/auth/register",
 *     operationId="register",
 *     tags={"Authentication"},
 *     summary="Register a new user account",
 *     description="Create a new user account with email and password. Automatically assigns Free plan.",
 *     @OA\RequestBody(
 *         required=true,
 *         description="User registration data",
 *         @OA\JsonContent(
 *             required={"name","email","password","password_confirmation"},
 *             @OA\Property(property="name", type="string", example="John Doe", description="Full name"),
 *             @OA\Property(property="email", type="string", format="email", example="john@example.com", description="Email address"),
 *             @OA\Property(property="password", type="string", format="password", example="SecurePass123!", description="Minimum 8 characters"),
 *             @OA\Property(property="password_confirmation", type="string", format="password", example="SecurePass123!", description="Must match password"),
 *             @OA\Property(property="phone", type="string", example="+1-555-0123", description="Phone number (optional)"),
 *             @OA\Property(property="company", type="string", example="Acme Corp", description="Company name (optional)"),
 *             @OA\Property(property="industry", type="string", example="Technology", description="Industry (optional)"),
 *             @OA\Property(property="location", type="string", example="New York, USA", description="Location (optional)")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="User registered successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="User registered successfully"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="name", type="string", example="John Doe"),
 *                 @OA\Property(property="email", type="string", example="john@example.com"),
 *                 @OA\Property(property="token", type="string", example="1|ABC123DEF456...")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="The given data was invalid."),
 *             @OA\Property(property="errors", type="object",
 *                 @OA\Property(property="email", type="array", @OA\Items(type="string", example="The email has already been taken."))
 *             )
 *         )
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/auth/login",
 *     operationId="login",
 *     tags={"Authentication"},
 *     summary="Login to user account",
 *     description="Authenticate with email and password to get API token",
 *     @OA\RequestBody(
 *         required=true,
 *         description="Login credentials",
 *         @OA\JsonContent(
 *             required={"email","password"},
 *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *             @OA\Property(property="password", type="string", format="password", example="SecurePass123!")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Login successful",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Login successful"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="name", type="string", example="John Doe"),
 *                 @OA\Property(property="email", type="string", example="john@example.com"),
 *                 @OA\Property(property="token", type="string", example="1|ABC123DEF456...")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Invalid credentials"
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/auth/logout",
 *     operationId="logout",
 *     tags={"Authentication"},
 *     summary="Logout from account",
 *     security={{"bearerToken":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Logout successful",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Logout successful"),
 *             @OA\Property(property="data", type="null")
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized")
 * )
 */

/**
 * @OA\Get(
 *     path="/auth/me",
 *     operationId="getProfile",
 *     tags={"Authentication"},
 *     summary="Get current user profile",
 *     security={{"bearerToken":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="User profile retrieved",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="name", type="string", example="John Doe"),
 *                 @OA\Property(property="email", type="string", example="john@example.com"),
 *                 @OA\Property(property="phone", type="string", example="+1-555-0123"),
 *                 @OA\Property(property="company", type="string", example="Acme Corp"),
 *                 @OA\Property(property="industry", type="string", example="Technology"),
 *                 @OA\Property(property="location", type="string", example="New York, USA"),
 *                 @OA\Property(property="status", type="string", enum={"active","inactive","suspended"}, example="active"),
 *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-01-20T10:30:00Z")
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized")
 * )
 */

/**
 * ==============================================================
 * BUSINESSES ENDPOINTS
 * ==============================================================
 */

/**
 * @OA\Get(
 *     path="/businesses",
 *     operationId="listBusinesses",
 *     tags={"Businesses"},
 *     summary="List all businesses",
 *     description="Retrieve paginated list of businesses for authenticated user",
 *     security={{"bearerToken":{}}},
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         description="Page number",
 *         required=false,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Parameter(
 *         name="per_page",
 *         in="query",
 *         description="Items per page",
 *         required=false,
 *         @OA\Schema(type="integer", example=15)
 *     ),
 *     @OA\Parameter(
 *         name="status",
 *         in="query",
 *         description="Filter by status",
 *         required=false,
 *         @OA\Schema(type="string", enum={"active","inactive"})
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Businesses retrieved",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="array", @OA\Items(
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="website_url", type="string", example="https://www.example.com"),
 *                 @OA\Property(property="business_name", type="string", example="Example Business"),
 *                 @OA\Property(property="industry", type="string", example="Technology"),
 *                 @OA\Property(property="country", type="string", example="USA"),
 *                 @OA\Property(property="city", type="string", example="New York"),
 *                 @OA\Property(property="status", type="string", example="active"),
 *                 @OA\Property(property="last_audited_at", type="string", format="date-time")
 *             )),
 *             @OA\Property(property="pagination", type="object",
 *                 @OA\Property(property="total", type="integer"),
 *                 @OA\Property(property="per_page", type="integer"),
 *                 @OA\Property(property="current_page", type="integer")
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized")
 * )
 */

/**
 * @OA\Post(
 *     path="/businesses",
 *     operationId="createBusiness",
 *     tags={"Businesses"},
 *     summary="Create a new business",
 *     security={{"bearerToken":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"website_url","business_name"},
 *             @OA\Property(property="website_url", type="string", format="url", example="https://www.example.com"),
 *             @OA\Property(property="business_name", type="string", example="Example Business"),
 *             @OA\Property(property="industry", type="string", example="Technology"),
 *             @OA\Property(property="country", type="string", example="USA"),
 *             @OA\Property(property="city", type="string", example="New York"),
 *             @OA\Property(property="description", type="string", example="A great business"),
 *             @OA\Property(property="keywords", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="logo_url", type="string", format="url")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Business created",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Business created successfully")
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized"),
 *     @OA\Response(response=422, description="Validation error")
 * )
 */

/**
 * @OA\Get(
 *     path="/businesses/{id}",
 *     operationId="showBusiness",
 *     tags={"Businesses"},
 *     summary="Get business details",
 *     security={{"bearerToken":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Business retrieved"
 *     ),
 *     @OA\Response(response=401, description="Unauthorized"),
 *     @OA\Response(response=404, description="Not found")
 * )
 */

/**
 * @OA\Put(
 *     path="/businesses/{id}",
 *     operationId="updateBusiness",
 *     tags={"Businesses"},
 *     summary="Update a business",
 *     security={{"bearerToken":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         @OA\JsonContent(
 *             @OA\Property(property="business_name", type="string"),
 *             @OA\Property(property="industry", type="string"),
 *             @OA\Property(property="status", type="string", enum={"active","inactive"})
 *         )
 *     ),
 *     @OA\Response(response=200, description="Business updated"),
 *     @OA\Response(response=401, description="Unauthorized"),
 *     @OA\Response(response=404, description="Not found")
 * )
 */

/**
 * @OA\Delete(
 *     path="/businesses/{id}",
 *     operationId="deleteBusiness",
 *     tags={"Businesses"},
 *     summary="Delete a business",
 *     security={{"bearerToken":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(response=200, description="Business deleted"),
 *     @OA\Response(response=401, description="Unauthorized"),
 *     @OA\Response(response=404, description="Not found")
 * )
 */

/**
 * ==============================================================
 * AUDITS ENDPOINTS
 * ==============================================================
 */

/**
 * @OA\Get(
 *     path="/audits",
 *     operationId="listAudits",
 *     tags={"Audits"},
 *     summary="List all audits",
 *     description="Retrieve paginated list of audits for authenticated user",
 *     security={{"bearerToken":{}}},
 *     @OA\Parameter(
 *         name="business_id",
 *         in="query",
 *         description="Filter by business ID",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Audits retrieved",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="array", @OA\Items(
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="business_id", type="integer", example=1),
 *                 @OA\Property(property="overall_score", type="integer", example=85),
 *                 @OA\Property(property="execution_time_ms", type="integer", example=3500),
 *                 @OA\Property(property="model_used", type="string", example="gpt-4-turbo"),
 *                 @OA\Property(property="created_at", type="string", format="date-time")
 *             ))
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized")
 * )
 */

/**
 * @OA\Get(
 *     path="/audits/{id}",
 *     operationId="showAudit",
 *     tags={"Audits"},
 *     summary="Get audit details with findings",
 *     description="Retrieve complete audit report with website audit, findings, and recommendations",
 *     security={{"bearerToken":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Audit retrieved with all details"
 *     ),
 *     @OA\Response(response=401, description="Unauthorized"),
 *     @OA\Response(response=404, description="Audit not found")
 * )
 */

/**
 * @OA\Post(
 *     path="/audits/trigger",
 *     operationId="triggerAudit",
 *     tags={"Audits"},
 *     summary="Start a new audit",
 *     description="Trigger a new audit for a business. Checks quota limits before starting.",
 *     security={{"bearerToken":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"business_id"},
 *             @OA\Property(property="business_id", type="integer", example=1)
 *         )
 *     ),
 *     @OA\Response(
 *         response=202,
 *         description="Audit started (processing in background)",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Audit started successfully"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="business_id", type="integer", example=1),
 *                 @OA\Property(property="overall_score", type="integer", example=0),
 *                 @OA\Property(property="created_at", type="string", format="date-time")
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized"),
 *     @OA\Response(response=429, description="Monthly audit quota exceeded")
 * )
 */

/**
 * @OA\Get(
 *     path="/audits/compare",
 *     operationId="compareAudits",
 *     tags={"Audits"},
 *     summary="Compare two audits",
 *     description="Get side-by-side comparison of two audits to see improvements or declines",
 *     security={{"bearerToken":{}}},
 *     @OA\Parameter(
 *         name="audit_1_id",
 *         in="query",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Parameter(
 *         name="audit_2_id",
 *         in="query",
 *         required=true,
 *         @OA\Schema(type="integer", example=2)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Audits compared"
 *     ),
 *     @OA\Response(response=401, description="Unauthorized")
 * )
 */

/**
 * @OA\Delete(
 *     path="/audits/{id}",
 *     operationId="deleteAudit",
 *     tags={"Audits"},
 *     summary="Delete an audit",
 *     security={{"bearerToken":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(response=200, description="Audit deleted"),
 *     @OA\Response(response=401, description="Unauthorized")
 * )
 */

/**
 * ==============================================================
 * SUBSCRIPTIONS ENDPOINTS
 * ==============================================================
 */

/**
 * @OA\Get(
 *     path="/subscription/plans",
 *     operationId="listSubscriptionPlans",
 *     tags={"Subscriptions"},
 *     summary="List all subscription plans",
 *     description="Public endpoint to view available subscription plans",
 *     @OA\Response(
 *         response=200,
 *         description="Plans retrieved",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="array", @OA\Items(
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="name", type="string", example="Pro"),
 *                 @OA\Property(property="slug", type="string", example="pro"),
 *                 @OA\Property(property="price_monthly", type="integer", example=2999),
 *                 @OA\Property(property="price_annual", type="integer", example=29990),
 *                 @OA\Property(property="audits_per_month", type="integer", example=50),
 *                 @OA\Property(property="businesses_limit", type="integer", example=5),
 *                 @OA\Property(property="support_level", type="string", example="email"),
 *                 @OA\Property(property="features", type="object")
 *             ))
 *         )
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/subscription/current",
 *     operationId="getCurrentSubscription",
 *     tags={"Subscriptions"},
 *     summary="Get current subscription",
 *     security={{"bearerToken":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Current subscription retrieved",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="status", type="string", enum={"active","canceled"}, example="active"),
 *                 @OA\Property(property="billing_cycle", type="string", enum={"monthly","annual"}, example="monthly"),
 *                 @OA\Property(property="current_period_start", type="string", format="date-time"),
 *                 @OA\Property(property="current_period_end", type="string", format="date-time"),
 *                 @OA\Property(property="price", type="number", format="float", example=29.99),
 *                 @OA\Property(property="plan", type="object",
 *                     @OA\Property(property="name", type="string", example="Pro"),
 *                     @OA\Property(property="audits_per_month", type="integer", example=50)
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized")
 * )
 */

/**
 * @OA\Post(
 *     path="/subscription/upgrade",
 *     operationId="upgradeSubscription",
 *     tags={"Subscriptions"},
 *     summary="Upgrade or downgrade subscription",
 *     security={{"bearerToken":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"subscription_plan_id"},
 *             @OA\Property(property="subscription_plan_id", type="integer", example=2),
 *             @OA\Property(property="billing_cycle", type="string", enum={"monthly","annual"}, example="monthly")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Subscription updated"
 *     ),
 *     @OA\Response(response=401, description="Unauthorized")
 * )
 */

/**
 * @OA\Post(
 *     path="/subscription/cancel",
 *     operationId="cancelSubscription",
 *     tags={"Subscriptions"},
 *     summary="Cancel subscription",
 *     security={{"bearerToken":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Subscription canceled"
 *     ),
 *     @OA\Response(response=401, description="Unauthorized")
 * )
 */

/**
 * @OA\Get(
 *     path="/subscription/usage",
 *     operationId="getSubscriptionUsage",
 *     tags={"Subscriptions"},
 *     summary="Get subscription usage statistics",
 *     description="View current month's usage including audits and business count against plan limits",
 *     security={{"bearerToken":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Usage statistics retrieved",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="audits_used", type="integer", example=15),
 *                 @OA\Property(property="audits_limit", type="integer", example=50),
 *                 @OA\Property(property="audits_percent", type="integer", example=30),
 *                 @OA\Property(property="businesses_used", type="integer", example=3),
 *                 @OA\Property(property="businesses_limit", type="integer", example=5),
 *                 @OA\Property(property="businesses_percent", type="integer", example=60),
 *                 @OA\Property(property="period_start", type="string", format="date-time"),
 *                 @OA\Property(property="period_end", type="string", format="date-time")
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized")
 * )
 */

class SwaggerDocumentation {}
