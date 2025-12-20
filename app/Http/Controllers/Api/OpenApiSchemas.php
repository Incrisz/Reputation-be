<?php

/**
 * @OA\Info(
 *    title="BizVisibility AI API",
 *    version="1.0.0",
 *    description="Comprehensive API for website audits, reputation management, and AI-powered recommendations",
 *    contact={
 *       "name": "Support",
 *       "email": "support@bizvisibility.ai"
 *    }
 * )
 * @OA\Server(
 *    url="/api/v1",
 *    description="API Server"
 * )
 * @OA\SecurityScheme(
 *    type="http",
 *    description="Login with username and password to get the authentication token",
 *    name="Token based based security",
 *    in="header",
 *    scheme="bearer",
 *    bearerFormat="JWT",
 *    securityScheme="bearerToken",
 * )
 * @OA\Schema(
 *    schema="Error",
 *    type="object",
 *    title="Error",
 *    properties={
 *       @OA\Property(property="success", type="boolean", example=false),
 *       @OA\Property(property="message", type="string"),
 *       @OA\Property(property="errors", type="object")
 *    }
 * )
 * @OA\Schema(
 *    schema="SuccessResponse",
 *    type="object",
 *    title="Success Response",
 *    properties={
 *       @OA\Property(property="success", type="boolean", example=true),
 *       @OA\Property(property="message", type="string"),
 *       @OA\Property(property="data", type="object")
 *    }
 * )
 * @OA\Schema(
 *    schema="PaginatedResponse",
 *    type="object",
 *    title="Paginated Response",
 *    properties={
 *       @OA\Property(property="success", type="boolean", example=true),
 *       @OA\Property(property="message", type="string"),
 *       @OA\Property(property="data", type="array", items={@OA\Items()}),
 *       @OA\Property(property="pagination", type="object", properties={
 *          @OA\Property(property="total", type="integer"),
 *          @OA\Property(property="per_page", type="integer"),
 *          @OA\Property(property="current_page", type="integer"),
 *          @OA\Property(property="last_page", type="integer"),
 *          @OA\Property(property="from", type="integer"),
 *          @OA\Property(property="to", type="integer")
 *       })
 *    }
 * )
 * @OA\Schema(
 *    schema="User",
 *    type="object",
 *    title="User",
 *    properties={
 *       @OA\Property(property="id", type="integer", example=1),
 *       @OA\Property(property="name", type="string", example="John Doe"),
 *       @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *       @OA\Property(property="phone", type="string", example="+1234567890"),
 *       @OA\Property(property="company", type="string", example="Acme Corp"),
 *       @OA\Property(property="industry", type="string", example="Technology"),
 *       @OA\Property(property="location", type="string", example="New York, USA"),
 *       @OA\Property(property="status", type="string", enum={"active", "inactive", "suspended"}, example="active"),
 *       @OA\Property(property="created_at", type="string", format="date-time"),
 *       @OA\Property(property="updated_at", type="string", format="date-time")
 *    }
 * )
 * @OA\Schema(
 *    schema="Business",
 *    type="object",
 *    title="Business",
 *    properties={
 *       @OA\Property(property="id", type="integer", example=1),
 *       @OA\Property(property="user_id", type="integer", example=1),
 *       @OA\Property(property="website_url", type="string", format="url", example="https://www.example.com"),
 *       @OA\Property(property="business_name", type="string", example="Example Business"),
 *       @OA\Property(property="industry", type="string", example="Technology"),
 *       @OA\Property(property="country", type="string", example="USA"),
 *       @OA\Property(property="city", type="string", example="New York"),
 *       @OA\Property(property="description", type="string"),
 *       @OA\Property(property="keywords", type="array", items={@OA\Items(type="string")}),
 *       @OA\Property(property="logo_url", type="string", format="url"),
 *       @OA\Property(property="status", type="string", enum={"active", "inactive"}, example="active"),
 *       @OA\Property(property="last_audited_at", type="string", format="date-time"),
 *       @OA\Property(property="created_at", type="string", format="date-time"),
 *       @OA\Property(property="updated_at", type="string", format="date-time")
 *    }
 * )
 * @OA\Schema(
 *    schema="Audit",
 *    type="object",
 *    title="Audit",
 *    properties={
 *       @OA\Property(property="id", type="integer", example=1),
 *       @OA\Property(property="user_id", type="integer", example=1),
 *       @OA\Property(property="business_id", type="integer", example=1),
 *       @OA\Property(property="overall_score", type="integer", minimum=0, maximum=100, example=85),
 *       @OA\Property(property="execution_time_ms", type="integer", example=3500),
 *       @OA\Property(property="model_used", type="string", example="gpt-4-turbo"),
 *       @OA\Property(property="metadata", type="object"),
 *       @OA\Property(property="created_at", type="string", format="date-time"),
 *       @OA\Property(property="updated_at", type="string", format="date-time")
 *    }
 * )
 * @OA\Schema(
 *    schema="SubscriptionPlan",
 *    type="object",
 *    title="Subscription Plan",
 *    properties={
 *       @OA\Property(property="id", type="integer", example=1),
 *       @OA\Property(property="name", type="string", example="Pro"),
 *       @OA\Property(property="slug", type="string", example="pro"),
 *       @OA\Property(property="price_monthly", type="integer", example=2999),
 *       @OA\Property(property="price_annual", type="integer", example=29990),
 *       @OA\Property(property="audits_per_month", type="integer", example=50),
 *       @OA\Property(property="businesses_limit", type="integer", example=5),
 *       @OA\Property(property="support_level", type="string", example="email"),
 *       @OA\Property(property="features", type="object"),
 *       @OA\Property(property="active", type="boolean", example=true)
 *    }
 * )
 * @OA\Schema(
 *    schema="Subscription",
 *    type="object",
 *    title="Subscription",
 *    properties={
 *       @OA\Property(property="id", type="integer", example=1),
 *       @OA\Property(property="user_id", type="integer", example=1),
 *       @OA\Property(property="subscription_plan_id", type="integer", example=1),
 *       @OA\Property(property="status", type="string", enum={"active", "paused", "canceled"}, example="active"),
 *       @OA\Property(property="billing_cycle", type="string", enum={"monthly", "annual"}, example="monthly"),
 *       @OA\Property(property="current_period_start", type="string", format="date-time"),
 *       @OA\Property(property="current_period_end", type="string", format="date-time"),
 *       @OA\Property(property="price", type="number", format="float", example=29.99),
 *       @OA\Property(property="renewal_at", type="string", format="date-time"),
 *       @OA\Property(property="canceled_at", type="string", format="date-time")
 *    }
 * )
 */
