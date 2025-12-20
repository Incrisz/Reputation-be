# BizVisibility AI - API Testing Guide

## Quick Start

### 1. Database Setup

Run the setup script to initialize the database with migrations and seed data:

```bash
chmod +x setup-db.sh
./setup-db.sh
```

Or manually:

```bash
php artisan migrate
php artisan db:seed
php artisan l5-swagger:generate
```

### 2. Start the Development Server

```bash
php artisan serve
```

Server will be available at: `http://localhost:8000`

### 3. Access Swagger Documentation

Open in your browser:
```
http://localhost:8000/api/docs
```

## API Overview

**Base URL:** `http://localhost:8000/api/v1`

**Authentication:** Sanctum Bearer Token (JWT-like)

All endpoints (except authentication and plans) require the `Authorization` header:
```
Authorization: Bearer YOUR_TOKEN_HERE
```

## Testing Workflow

### Step 1: Register a New User

**POST** `/auth/register`

```json
{
  "name": "Test User",
  "email": "testuser@example.com",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!",
  "company": "Test Company",
  "industry": "Technology",
  "location": "New York, USA"
}
```

**Response:**
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "id": 6,
    "name": "Test User",
    "email": "testuser@example.com",
    "token": "1|ABC123DEF456GHI789JKL..."
  }
}
```

Save the `token` for subsequent requests.

---

### Step 2: Login (Alternative to Register)

**POST** `/auth/login`

```json
{
  "email": "test@example.com",
  "password": "password"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "id": 1,
    "name": "Test User",
    "email": "test@example.com",
    "token": "1|ABC123DEF456GHI789JKL..."
  }
}
```

---

### Step 3: View Subscription Plans

**GET** `/subscription/plans` (No authentication required)

**Response:**
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
      "support_level": "community",
      "features": { ... }
    },
    {
      "id": 2,
      "name": "Pro",
      "slug": "pro",
      "price_monthly": 2999,
      "price_annual": 29990,
      "audits_per_month": 50,
      "businesses_limit": 5,
      "support_level": "email",
      "features": { ... }
    }
    // ... Business and Enterprise plans
  ]
}
```

---

### Step 4: Create a Business

**POST** `/businesses` (Requires authentication)

```json
{
  "website_url": "https://www.mycompany.com",
  "business_name": "My Company",
  "industry": "Technology",
  "country": "USA",
  "city": "San Francisco",
  "description": "A tech company specializing in web solutions",
  "keywords": ["technology", "web development", "software"],
  "logo_url": "https://mycompany.com/logo.png"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Business created successfully",
  "data": {
    "id": 1,
    "user_id": 1,
    "website_url": "https://www.mycompany.com",
    "business_name": "My Company",
    "industry": "Technology",
    "country": "USA",
    "city": "San Francisco",
    "status": "active",
    "created_at": "2025-01-20T10:30:00Z"
  }
}
```

---

### Step 5: List Your Businesses

**GET** `/businesses` (Requires authentication)

**Query Parameters:**
- `page=1` - Page number
- `per_page=15` - Items per page
- `status=active` - Filter by status

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "website_url": "https://www.mycompany.com",
      "business_name": "My Company",
      "industry": "Technology",
      "status": "active",
      "last_audited_at": null,
      "created_at": "2025-01-20T10:30:00Z"
    }
  ],
  "pagination": {
    "total": 1,
    "per_page": 15,
    "current_page": 1,
    "last_page": 1,
    "from": 1,
    "to": 1
  }
}
```

---

### Step 6: Trigger an Audit

**POST** `/audits/trigger` (Requires authentication)

```json
{
  "business_id": 1
}
```

**Response:**
```json
{
  "success": true,
  "message": "Audit started successfully",
  "data": {
    "id": 1,
    "user_id": 1,
    "business_id": 1,
    "overall_score": 0,
    "execution_time_ms": 0,
    "model_used": "gpt-4-turbo",
    "metadata": {
      "status": "processing",
      "ip_address": "127.0.0.1"
    },
    "created_at": "2025-01-20T10:30:00Z"
  }
}
```

**Note:** Audit runs in background. Score will update once processing is complete.

---

### Step 7: List Audits

**GET** `/audits` (Requires authentication)

**Query Parameters:**
- `business_id=1` - Filter by business
- `page=1` - Page number

**Response:**
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
      "model_used": "gpt-4-turbo",
      "created_at": "2025-01-20T10:30:00Z",
      "business": {
        "id": 1,
        "business_name": "My Company"
      }
    }
  ],
  "pagination": { ... }
}
```

---

### Step 8: Get Audit Details

**GET** `/audits/{id}` (Requires authentication)

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 1,
    "business_id": 1,
    "overall_score": 85,
    "execution_time_ms": 3500,
    "model_used": "gpt-4-turbo",
    "business": { ... },
    "website_audit": {
      "id": 1,
      "audit_id": 1,
      "technical_seo_score": 80,
      "content_quality_score": 90,
      "findings": [
        {
          "id": 1,
          "category": "seo",
          "type": "issue",
          "finding": "Missing meta descriptions",
          "severity": "high"
        },
        {
          "id": 2,
          "category": "performance",
          "type": "strength",
          "finding": "Good mobile optimization"
        }
      ]
    },
    "social_media_profiles": [
      {
        "id": 1,
        "platform": "facebook",
        "url": "https://facebook.com/business",
        "presence_detected": true,
        "verified": true,
        "followers_estimate": 5000
      }
    ],
    "google_business_profile": {
      "id": 1,
      "detected": true,
      "listing_quality_score": 85,
      "review_count": 42,
      "rating": 4.8,
      "complete_profile": true
    },
    "ai_recommendations": [
      {
        "id": 1,
        "category": "seo",
        "priority": "high",
        "recommendation": "Improve your seo strategy",
        "implementation_effort": "moderate",
        "impact_level": "high"
      }
    ]
  }
}
```

---

### Step 9: Compare Two Audits

**GET** `/audits/compare?audit_1_id=1&audit_2_id=2` (Requires authentication)

**Response:**
```json
{
  "success": true,
  "data": {
    "audit_1": { ... },
    "audit_2": { ... },
    "comparison": {
      "score_improvement": 10,
      "key_improvements": ["SEO improved", "Mobile responsiveness"],
      "areas_declined": ["Page load time"]
    }
  }
}
```

---

### Step 10: Check Subscription Usage

**GET** `/subscription/usage` (Requires authentication)

**Response:**
```json
{
  "success": true,
  "data": {
    "audits_used": 2,
    "audits_limit": 50,
    "audits_percent": 4,
    "businesses_used": 1,
    "businesses_limit": 5,
    "businesses_percent": 20,
    "period_start": "2025-01-01T00:00:00Z",
    "period_end": "2025-02-01T00:00:00Z"
  }
}
```

---

### Step 11: View Current Subscription

**GET** `/subscription/current` (Requires authentication)

**Response:**
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
    "price": 0,
    "plan": {
      "id": 1,
      "name": "Free",
      "slug": "free",
      "audits_per_month": 5,
      "businesses_limit": 1
    }
  }
}
```

---

### Step 12: Upgrade Subscription

**POST** `/subscription/upgrade` (Requires authentication)

```json
{
  "subscription_plan_id": 2,
  "billing_cycle": "monthly"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Subscription updated successfully",
  "data": {
    "id": 1,
    "status": "active",
    "subscription_plan_id": 2,
    "billing_cycle": "monthly",
    "price": 29.99,
    "plan": {
      "name": "Pro",
      "audits_per_month": 50,
      "businesses_limit": 5
    }
  }
}
```

---

### Step 13: Get User Profile

**GET** `/auth/me` (Requires authentication)

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Test User",
    "email": "test@example.com",
    "phone": "+1234567890",
    "company": "Test Company",
    "industry": "Technology",
    "location": "New York, USA",
    "status": "active",
    "created_at": "2025-01-15T10:30:00Z"
  }
}
```

---

### Step 14: Logout

**POST** `/auth/logout` (Requires authentication)

**Response:**
```json
{
  "success": true,
  "message": "Logout successful",
  "data": null
}
```

---

## Error Handling

### 401 Unauthorized
Missing or invalid authentication token

**Response:**
```json
{
  "success": false,
  "message": "Unauthenticated."
}
```

### 422 Validation Error
Invalid request data

**Response:**
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email has already been taken."],
    "password": ["The password field is required."]
  }
}
```

### 429 Quota Exceeded
Monthly audit limit reached

**Response:**
```json
{
  "success": false,
  "message": "Monthly audit quota exceeded"
}
```

### 404 Not Found
Resource doesn't exist or user doesn't have access

**Response:**
```json
{
  "success": false,
  "message": "Resource not found"
}
```

---

## Testing with cURL

### Register a User
```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "SecurePass123!",
    "password_confirmation": "SecurePass123!",
    "company": "Tech Corp",
    "industry": "Technology"
  }'
```

### Create a Business
```bash
curl -X POST http://localhost:8000/api/v1/businesses \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "website_url": "https://example.com",
    "business_name": "Example Business",
    "industry": "Technology",
    "country": "USA",
    "city": "New York"
  }'
```

### Trigger an Audit
```bash
curl -X POST http://localhost:8000/api/v1/audits/trigger \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"business_id": 1}'
```

### List Audits
```bash
curl -X GET http://localhost:8000/api/v1/audits \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## Postman Collection

A Postman collection is available in `postman_collection.json`. 

**To import:**
1. Open Postman
2. Click "Import"
3. Select `postman_collection.json`
4. Set the `base_url` variable to `http://localhost:8000/api/v1`
5. Set the `token` variable after login

---

## Database Seeded Data

The seeder creates test data:

**Plans:**
- Free: 5 audits/month, 1 business
- Pro: 50 audits/month, 5 businesses, $29.99/month
- Business: Unlimited audits, 25 businesses, $99.99/month
- Enterprise: All features, custom pricing

**Test Users:** 5 sample users with:
- Complete business profiles
- 2 audits per business
- Website audit findings (issues and strengths)
- Social media profiles detected
- Google Business Profile data
- AI recommendations
- Activity logs
- Notifications

---

## API Rate Limiting

Currently not implemented. Add to `app/Http/Middleware/` when needed:
```php
'throttle' => 'api:60,1'  // 60 requests per minute
```

---

## Next Steps

1. **Implement Audit Processing**: Create a Job to process audits asynchronously
2. **Add Payment Processing**: Integrate Stripe webhooks for subscription management
3. **Email Notifications**: Set up email sending for audit completion
4. **Export Functionality**: Generate PDF/HTML reports
5. **Advanced Analytics**: Dashboard for audit trends and metrics
6. **Team Features**: Multi-user collaboration within subscriptions
7. **API Rate Limiting**: Protect against abuse
8. **Caching Strategy**: Cache frequently accessed data
