<?php

namespace App\Http\Controllers\Api;

/**
 * @OA\Info(
 *    title="BizVisibility AI - Complete API",
 *    version="1.0.0",
 *    description="Complete REST API for website audits, business reputation management, SEO analysis, and AI-powered recommendations. Multi-tenant SaaS platform.",
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
 *    url="http://localhost:8000/api/v1",
 *    description="Local Development Server"
 * )
 * @OA\SecurityScheme(
 *    type="http",
 *    description="Laravel Sanctum token",
 *    name="bearerToken",
 *    in="header",
 *    scheme="bearer",
 *    bearerFormat="Sanctum"
 * )
 */

/**
 * ============================================================
 * AUTHENTICATION ENDPOINTS
 * ============================================================
 */

/**
 * @OA\Post(
 *     path="/auth/register",
 *     operationId="register",
 *     tags={"Authentication"},
 *     summary="Register a new user account",
 *     description="Create a new user account. Returns authentication token.",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"name","email","password","password_confirmation"},
 *             @OA\Property(property="name", type="string", example="John Doe"),
 *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *             @OA\Property(property="password", type="string", format="password", example="password123"),
 *             @OA\Property(property="password_confirmation", type="string", format="password", example="password123")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="User registered successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Registration successful"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="user", type="object"),
 *                 @OA\Property(property="token", type="string")
 *             )
 *         )
 *     ),
 *     @OA\Response(response=422, description="Validation error")
 * )
 */

/**
 * @OA\Post(
 *     path="/auth/login",
 *     operationId="login",
 *     tags={"Authentication"},
 *     summary="Login user",
 *     description="Authenticate user with email and password. Returns authentication token.",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"email","password"},
 *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *             @OA\Property(property="password", type="string", format="password", example="password123")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Login successful",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="user", type="object"),
 *                 @OA\Property(property="token", type="string")
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Invalid credentials")
 * )
 */

/**
 * @OA\Post(
 *     path="/auth/logout",
 *     operationId="logout",
 *     tags={"Authentication"},
 *     summary="Logout user",
 *     description="Invalidate the authentication token",
 *     security={{"bearerToken":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Logout successful",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Logged out successfully")
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
 *     description="Retrieve the authenticated user's profile information",
 *     security={{"bearerToken":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="User profile retrieved",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object")
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized")
 * )
 */

/**
 * ============================================================
 * BUSINESSES ENDPOINTS
 * ============================================================
 */

/**
 * @OA\Get(
 *     path="/businesses",
 *     operationId="listBusinesses",
 *     tags={"Businesses"},
 *     summary="List all businesses",
 *     description="Get paginated list of user's businesses",
 *     security={{"bearerToken":{}}},
 *     @OA\Parameter(name="page", in="query", description="Page number", schema={"type":"integer"}),
 *     @OA\Parameter(name="per_page", in="query", description="Items per page", schema={"type":"integer"}),
 *     @OA\Response(
 *         response=200,
 *         description="Businesses retrieved",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="array", items={"type":"object"}),
 *             @OA\Property(property="meta", type="object")
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
 *     description="Add a new business to the user's portfolio",
 *     security={{"bearerToken":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"website_url","business_name","industry","country","city"},
 *             @OA\Property(property="website_url", type="string", example="https://example.com"),
 *             @OA\Property(property="business_name", type="string", example="My Business"),
 *             @OA\Property(property="industry", type="string", example="Technology"),
 *             @OA\Property(property="country", type="string", example="USA"),
 *             @OA\Property(property="city", type="string", example="San Francisco"),
 *             @OA\Property(property="description", type="string", example="Business description"),
 *             @OA\Property(property="keywords", type="array", items={"type":"string"})
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Business created",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object")
 *         )
 *     ),
 *     @OA\Response(response=422, description="Validation error")
 * )
 */

/**
 * @OA\Get(
 *     path="/businesses/{id}",
 *     operationId="getBusiness",
 *     tags={"Businesses"},
 *     summary="Get business details",
 *     description="Retrieve detailed information about a specific business",
 *     security={{"bearerToken":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, description="Business ID", schema={"type":"integer"}),
 *     @OA\Response(
 *         response=200,
 *         description="Business retrieved",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object")
 *         )
 *     ),
 *     @OA\Response(response=404, description="Business not found")
 * )
 */

/**
 * @OA\Put(
 *     path="/businesses/{id}",
 *     operationId="updateBusiness",
 *     tags={"Businesses"},
 *     summary="Update business",
 *     description="Update business information",
 *     security={{"bearerToken":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, schema={"type":"integer"}),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent()
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Business updated",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object")
 *         )
 *     ),
 *     @OA\Response(response=404, description="Business not found")
 * )
 */

/**
 * @OA\Delete(
 *     path="/businesses/{id}",
 *     operationId="deleteBusiness",
 *     tags={"Businesses"},
 *     summary="Delete business",
 *     description="Remove a business from the system",
 *     security={{"bearerToken":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, schema={"type":"integer"}),
 *     @OA\Response(
 *         response=204,
 *         description="Business deleted"
 *     ),
 *     @OA\Response(response=404, description="Business not found")
 * )
 */

/**
 * ============================================================
 * AUDITS ENDPOINTS
 * ============================================================
 */

/**
 * @OA\Get(
 *     path="/audits",
 *     operationId="listAudits",
 *     tags={"Audits"},
 *     summary="List all audits",
 *     description="Get paginated list of audits",
 *     security={{"bearerToken":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Audits retrieved",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="array", items={"type":"object"})
 *         )
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/audits/trigger",
 *     operationId="triggerAudit",
 *     tags={"Audits"},
 *     summary="Trigger a new audit",
 *     description="Start a comprehensive audit for a business",
 *     security={{"bearerToken":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"business_id"},
 *             @OA\Property(property="business_id", type="integer")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Audit triggered",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object")
 *         )
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/audits/{id}",
 *     operationId="getAudit",
 *     tags={"Audits"},
 *     summary="Get audit details",
 *     description="Retrieve a specific audit report",
 *     security={{"bearerToken":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, schema={"type":"integer"}),
 *     @OA\Response(
 *         response=200,
 *         description="Audit retrieved",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object")
 *         )
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/audits/compare",
 *     operationId="compareAudits",
 *     tags={"Audits"},
 *     summary="Compare two audits",
 *     description="Compare metrics between two audit reports",
 *     security={{"bearerToken":{}}},
 *     @OA\Parameter(name="audit1_id", in="query", required=true, schema={"type":"integer"}),
 *     @OA\Parameter(name="audit2_id", in="query", required=true, schema={"type":"integer"}),
 *     @OA\Response(
 *         response=200,
 *         description="Comparison retrieved"
 *     )
 * )
 */

/**
 * @OA\Delete(
 *     path="/audits/{id}",
 *     operationId="deleteAudit",
 *     tags={"Audits"},
 *     summary="Delete audit",
 *     description="Remove an audit record",
 *     security={{"bearerToken":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, schema={"type":"integer"}),
 *     @OA\Response(
 *         response=204,
 *         description="Audit deleted"
 *     )
 * )
 */

/**
 * ============================================================
 * SUBSCRIPTIONS ENDPOINTS
 * ============================================================
 */

/**
 * @OA\Get(
 *     path="/subscription/plans",
 *     operationId="listSubscriptionPlans",
 *     tags={"Subscriptions"},
 *     summary="Get subscription plans",
 *     description="List all available subscription plans (public endpoint)",
 *     @OA\Response(
 *         response=200,
 *         description="Plans retrieved",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="array", items={"type":"object"})
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
 *     description="Retrieve user's active subscription",
 *     security={{"bearerToken":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Subscription retrieved",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object")
 *         )
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/subscription/upgrade",
 *     operationId="upgradeSubscription",
 *     tags={"Subscriptions"},
 *     summary="Upgrade subscription",
 *     description="Upgrade to a higher tier plan",
 *     security={{"bearerToken":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"subscription_plan_id"},
 *             @OA\Property(property="subscription_plan_id", type="integer")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Subscription upgraded"
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/subscription/cancel",
 *     operationId="cancelSubscription",
 *     tags={"Subscriptions"},
 *     summary="Cancel subscription",
 *     description="Cancel the active subscription",
 *     security={{"bearerToken":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Subscription cancelled"
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/subscription/usage",
 *     operationId="getSubscriptionUsage",
 *     tags={"Subscriptions"},
 *     summary="Get subscription usage",
 *     description="Get current usage statistics against plan limits",
 *     security={{"bearerToken":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Usage retrieved",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="audits_used", type="integer"),
 *                 @OA\Property(property="audits_limit", type="integer"),
 *                 @OA\Property(property="businesses_used", type="integer"),
 *                 @OA\Property(property="businesses_limit", type="integer")
 *             )
 *         )
 *     )
 * )
 */
