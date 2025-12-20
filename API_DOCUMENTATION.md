# BizVisibility AI API Documentation

## Overview

BizVisibility AI is a comprehensive SaaS platform for website audits, online presence detection, and AI-powered recommendations. This API provides complete access to all platform features.

## Base URL

```
https://api.bizvisibility.ai/api/v1
```

## Authentication

All protected endpoints require a Bearer token in the `Authorization` header:

```
Authorization: Bearer {token}
```

### Getting a Token

1. **Register**: Create a new account
   ```bash
   POST /auth/register
   Content-Type: application/json
   
   {
     "name": "John Doe",
     "email": "john@example.com",
     "password": "secure_password_123",
     "password_confirmation": "secure_password_123",
     "company": "Acme Corp",
     "industry": "Technology"
   }
   ```

2. **Login**: Get authentication token
   ```bash
   POST /auth/login
   Content-Type: application/json
   
   {
     "email": "john@example.com",
     "password": "secure_password_123"
   }
   ```

Response:
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
  }
}
```

## API Endpoints

### Authentication

#### Register User
```
POST /auth/register
```

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "secure_password_123",
  "password_confirmation": "secure_password_123",
  "phone": "+1234567890",
  "company": "Acme Corp",
  "industry": "Technology",
  "location": "New York, USA"
}
```

**Response:** `201 Created`
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
  }
}
```

#### Login User
```
POST /auth/login
```

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "secure_password_123"
}
```

**Response:** `200 OK`

#### Logout User
```
POST /auth/logout
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Logout successful"
}
```

#### Get Current User Profile
```
GET /auth/me
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+1234567890",
    "company": "Acme Corp",
    "industry": "Technology",
    "location": "New York, USA",
    "status": "active",
    "created_at": "2025-01-20T10:30:00Z"
  }
}
```

### Businesses

#### List Businesses
```
GET /businesses
Authorization: Bearer {token}
```

**Query Parameters:**
- `page` (int) - Page number for pagination (default: 1)
- `per_page` (int) - Items per page (default: 15)
- `status` (string) - Filter by status: `active` or `inactive`

**Response:** `200 OK`
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "website_url": "https://www.example.com",
      "business_name": "Example Business",
      "industry": "Technology",
      "country": "USA",
      "city": "New York",
      "status": "active",
      "last_audited_at": "2025-01-20T10:30:00Z",
      "created_at": "2025-01-15T10:30:00Z"
    }
  ],
  "pagination": {
    "total": 10,
    "per_page": 15,
    "current_page": 1,
    "last_page": 1,
    "from": 1,
    "to": 10
  }
}
```

#### Create Business
```
POST /businesses
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "website_url": "https://www.example.com",
  "business_name": "Example Business",
  "industry": "Technology",
  "country": "USA",
  "city": "New York",
  "description": "A great business",
  "keywords": ["seo", "digital"],
  "logo_url": "https://example.com/logo.png"
}
```

**Response:** `201 Created`

#### Get Business
```
GET /businesses/{id}
Authorization: Bearer {token}
```

**Response:** `200 OK`

#### Update Business
```
PUT /businesses/{id}
PATCH /businesses/{id}
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:** (all fields optional)
```json
{
  "business_name": "Updated Name",
  "industry": "Healthcare",
  "status": "active"
}
```

**Response:** `200 OK`

#### Delete Business
```
DELETE /businesses/{id}
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Business deleted successfully"
}
```

### Audits

#### List Audits
```
GET /audits
Authorization: Bearer {token}
```

**Query Parameters:**
- `business_id` (int) - Filter by business
- `page` (int) - Page number
- `per_page` (int) - Items per page (default: 15)

**Response:** `200 OK`
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "business_id": 1,
      "overall_score": 85,
      "execution_time_ms": 3500,
      "model_used": "gpt-4",
      "created_at": "2025-01-20T10:30:00Z",
      "business": {
        "id": 1,
        "business_name": "Example Business"
      }
    }
  ],
  "pagination": {}
}
```

#### Get Audit Details
```
GET /audits/{id}
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 1,
    "business_id": 1,
    "overall_score": 85,
    "execution_time_ms": 3500,
    "created_at": "2025-01-20T10:30:00Z",
    "website_audit": {
      "id": 1,
      "technical_seo_score": 80,
      "content_quality_score": 90
    },
    "website_audit_findings": [
      {
        "id": 1,
        "category": "seo",
        "type": "issue",
        "finding": "Missing meta descriptions",
        "severity": "high"
      }
    ],
    "social_media_profiles": [
      {
        "platform": "facebook",
        "url": "https://facebook.com/business",
        "presence_detected": true,
        "verified": false
      }
    ],
    "google_business_profile": {
      "detected": true,
      "listing_quality_score": 85,
      "review_count": 45,
      "rating": 4.5
    },
    "ai_recommendations": [
      {
        "category": "seo",
        "priority": "high",
        "recommendation": "Improve technical SEO",
        "implementation_effort": "moderate"
      }
    ]
  }
}
```

#### Trigger Audit
```
POST /audits/trigger
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "business_id": 1
}
```

**Response:** `202 Accepted`
```json
{
  "success": true,
  "message": "Audit started successfully",
  "data": {
    "id": 1,
    "user_id": 1,
    "business_id": 1,
    "overall_score": 0,
    "status": "processing",
    "created_at": "2025-01-20T10:30:00Z"
  }
}
```

#### Compare Audits
```
GET /audits/compare
Authorization: Bearer {token}
```

**Query Parameters:**
- `audit_1_id` (int) - First audit ID (required)
- `audit_2_id` (int) - Second audit ID (required)

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "audit_1": {},
    "audit_2": {},
    "score_improvement": 10,
    "key_improvements": [],
    "areas_declined": []
  }
}
```

#### Delete Audit
```
DELETE /audits/{id}
Authorization: Bearer {token}
```

**Response:** `200 OK`

### Subscriptions

#### List Plans
```
GET /subscription/plans
```

**Response:** `200 OK`
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Free",
      "slug": "free",
      "price_monthly": 0,
      "price_annual": 0,
      "audits_per_month": 5,
      "businesses_limit": 1,
      "features": {}
    },
    {
      "id": 2,
      "name": "Pro",
      "slug": "pro",
      "price_monthly": 2999,
      "price_annual": 29990,
      "audits_per_month": 50,
      "businesses_limit": 5,
      "features": {}
    }
  ]
}
```

#### Get Current Subscription
```
GET /subscription/current
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 1,
    "subscription_plan_id": 1,
    "status": "active",
    "billing_cycle": "monthly",
    "current_period_start": "2025-01-20T00:00:00Z",
    "current_period_end": "2025-02-20T00:00:00Z",
    "price": 29.99,
    "plan": {
      "id": 1,
      "name": "Pro",
      "audits_per_month": 50,
      "businesses_limit": 5
    }
  }
}
```

#### Get Subscription Usage
```
GET /subscription/usage
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
  "success": true,
  "data": {
    "audits_used": 15,
    "audits_limit": 50,
    "audits_percent": 30,
    "businesses_used": 3,
    "businesses_limit": 5,
    "businesses_percent": 60,
    "period_start": "2025-01-20T00:00:00Z",
    "period_end": "2025-02-20T00:00:00Z"
  }
}
```

#### Upgrade/Downgrade Subscription
```
POST /subscription/upgrade
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "subscription_plan_id": 2,
  "billing_cycle": "monthly"
}
```

**Response:** `200 OK`

#### Cancel Subscription
```
POST /subscription/cancel
Authorization: Bearer {token}
```

**Response:** `200 OK`
```json
{
  "success": true,
  "message": "Subscription canceled successfully"
}
```

## Response Format

All API responses follow a standard format:

### Success Response
```json
{
  "success": true,
  "message": "Success message",
  "data": {}
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error message",
  "errors": {}
}
```

### Paginated Response
```json
{
  "success": true,
  "message": "Success message",
  "data": [],
  "pagination": {
    "total": 100,
    "per_page": 15,
    "current_page": 1,
    "last_page": 7,
    "from": 1,
    "to": 15
  }
}
```

## HTTP Status Codes

- `200 OK` - Successful GET, PUT, PATCH request
- `201 Created` - Successful POST request
- `202 Accepted` - Request accepted but still processing
- `204 No Content` - Successful DELETE request
- `400 Bad Request` - Validation error
- `401 Unauthorized` - Missing or invalid authentication
- `403 Forbidden` - Access denied
- `404 Not Found` - Resource not found
- `422 Unprocessable Entity` - Validation failed
- `429 Too Many Requests` - Rate limit exceeded
- `500 Internal Server Error` - Server error

## Rate Limiting

API rate limits:
- Free plan: 100 requests/hour
- Pro plan: 1,000 requests/hour
- Business plan: Unlimited

Rate limit headers are included in responses:
- `X-RateLimit-Limit`
- `X-RateLimit-Remaining`
- `X-RateLimit-Reset`

## Pagination

Paginated endpoints accept:
- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 15, max: 100)

## Errors

### Common Error Responses

**Validation Error (422)**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

**Authentication Error (401)**
```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

**Authorization Error (403)**
```json
{
  "success": false,
  "message": "This action is unauthorized"
}
```

**Not Found (404)**
```json
{
  "success": false,
  "message": "Resource not found"
}
```

## Example Usage with cURL

### Register
```bash
curl -X POST https://api.bizvisibility.ai/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "secure_password_123",
    "password_confirmation": "secure_password_123",
    "company": "Acme Corp",
    "industry": "Technology"
  }'
```

### Login
```bash
curl -X POST https://api.bizvisibility.ai/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "secure_password_123"
  }'
```

### Create Business
```bash
curl -X POST https://api.bizvisibility.ai/api/v1/businesses \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "website_url": "https://www.example.com",
    "business_name": "Example Business",
    "industry": "Technology"
  }'
```

### Trigger Audit
```bash
curl -X POST https://api.bizvisibility.ai/api/v1/audits/trigger \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "business_id": 1
  }'
```

## Support

For API support, contact: api-support@bizvisibility.ai
