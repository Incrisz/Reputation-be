# Quick Start Guide - BizVisibility AI API

## 1. Setup & Installation

```bash
# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate app key
php artisan key:generate

# Create database
mysql -u root -e "CREATE DATABASE bizvisibility;"

# Run migrations
php artisan migrate

# Seed with test data
php artisan db:seed

# Start server
php artisan serve
```

## 2. Test Data Created by Seeder

After running `php artisan db:seed`, you'll have:

**5 Test Users** with emails: faker-generated
- Each with a subscription (Free, Pro, Business, or Enterprise)
- Each with notification preferences
- Accessible via login

**Subscription Plans**:
- Free: 5 audits/month, 1 business (Free)
- Pro: 50 audits/month, 5 businesses ($29.99/mo)
- Business: Unlimited audits, 25 businesses ($99.99/mo)
- Enterprise: Unlimited everything ($299.99/mo)

**15 Businesses** (3 per user)
- With realistic website URLs
- Sample industries and locations
- Ready for auditing

**30 Audits** (2 per business)
- Scores ranging 40-95
- Complete findings and recommendations
- Social media detection data
- Google Business Profile data

## 3. Authentication Flow

### Register New User
```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "company": "Acme Corp",
    "industry": "Technology"
  }'
```

Response includes `token` - save this!

### Login Existing User
```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'
```

### Use Token in Requests
```bash
curl -X GET http://localhost:8000/api/v1/auth/me \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

**All protected endpoints require this header!**

## 4. Common API Operations

### List Your Businesses
```bash
curl -X GET http://localhost:8000/api/v1/businesses \
  -H "Authorization: Bearer TOKEN"
```

### Create a Business
```bash
curl -X POST http://localhost:8000/api/v1/businesses \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "website_url": "https://example.com",
    "business_name": "My Business",
    "industry": "Technology",
    "country": "USA",
    "city": "New York"
  }'
```

### Trigger an Audit
```bash
curl -X POST http://localhost:8000/api/v1/audits/trigger \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"business_id": 1}'
```

**Note**: Response status is 202 (accepted) - audit processes in background

### Get Audit Results
```bash
curl -X GET http://localhost:8000/api/v1/audits/1 \
  -H "Authorization: Bearer TOKEN"
```

### Compare Two Audits
```bash
curl -X GET http://localhost:8000/api/v1/audits/compare?audit_1_id=1&audit_2_id=2 \
  -H "Authorization: Bearer TOKEN"
```

### Check Your Subscription
```bash
curl -X GET http://localhost:8000/api/v1/subscription/current \
  -H "Authorization: Bearer TOKEN"
```

### Check Usage Limits
```bash
curl -X GET http://localhost:8000/api/v1/subscription/usage \
  -H "Authorization: Bearer TOKEN"
```

### Upgrade Subscription
```bash
curl -X POST http://localhost:8000/api/v1/subscription/upgrade \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "subscription_plan_id": 2,
    "billing_cycle": "monthly"
  }'
```

## 5. Response Format

All responses follow this format:

### Success
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

### Error
```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": ["Error message"]
  }
}
```

### Paginated
```json
{
  "success": true,
  "data": [ ... ],
  "pagination": {
    "total": 100,
    "per_page": 15,
    "current_page": 1,
    "last_page": 7
  }
}
```

## 6. API Endpoints Reference

| Method | Endpoint | Auth | Purpose |
|--------|----------|------|---------|
| POST | `/auth/register` | ❌ | Register user |
| POST | `/auth/login` | ❌ | Login user |
| POST | `/auth/logout` | ✅ | Logout user |
| GET | `/auth/me` | ✅ | Get profile |
| GET | `/businesses` | ✅ | List businesses |
| POST | `/businesses` | ✅ | Create business |
| GET | `/businesses/{id}` | ✅ | Get business |
| PUT | `/businesses/{id}` | ✅ | Update business |
| DELETE | `/businesses/{id}` | ✅ | Delete business |
| GET | `/audits` | ✅ | List audits |
| GET | `/audits/{id}` | ✅ | Get audit details |
| POST | `/audits/trigger` | ✅ | Start audit |
| GET | `/audits/compare` | ✅ | Compare audits |
| DELETE | `/audits/{id}` | ✅ | Delete audit |
| GET | `/subscription/plans` | ❌ | List plans |
| GET | `/subscription/current` | ✅ | Get subscription |
| POST | `/subscription/upgrade` | ✅ | Change plan |
| POST | `/subscription/cancel` | ✅ | Cancel subscription |
| GET | `/subscription/usage` | ✅ | Get usage metrics |

## 7. Common Errors & Solutions

### 401 Unauthorized
**Problem**: Missing or invalid token
```
Authorization: Bearer TOKEN_HERE
```
Make sure you include the full "Bearer {token}" format

### 422 Unprocessable Entity
**Problem**: Validation failed
Check the `errors` object in response for specific field errors

### 403 Forbidden
**Problem**: Can't access someone else's resource
Users can only manage their own businesses and audits

### 429 Too Many Requests
**Problem**: Rate limit exceeded
Free plan: 100 requests/hour
Pro plan: 1,000 requests/hour

### 404 Not Found
**Problem**: Resource doesn't exist
Check the ID exists and belongs to your user

## 8. Database Relationships

```
User
├── Subscription (1:1)
│   └── SubscriptionPlan
├── Business (1:M)
│   └── Audit (1:M)
│       ├── WebsiteAudit (1:1)
│       ├── SocialMediaProfiles (1:M)
│       ├── GoogleBusinessProfile (1:1)
│       ├── AiRecommendations (1:M)
│       └── AuditReports (1:M)
├── Notifications (1:M)
└── ActivityLogs (1:M)
```

## 9. Query Examples

### Filter Businesses by Status
```bash
curl -X GET "http://localhost:8000/api/v1/businesses?status=active" \
  -H "Authorization: Bearer TOKEN"
```

### Pagination
```bash
curl -X GET "http://localhost:8000/api/v1/businesses?page=2&per_page=10" \
  -H "Authorization: Bearer TOKEN"
```

### Filter Audits by Business
```bash
curl -X GET "http://localhost:8000/api/v1/audits?business_id=1" \
  -H "Authorization: Bearer TOKEN"
```

## 10. Activity Logging

Every action is logged to `activity_logs` table:
- User logins/logouts
- Business CRUD operations
- Audit operations
- Subscription changes

Check activity logs via database or future admin API:
```bash
SELECT * FROM activity_logs WHERE user_id = 1 ORDER BY created_at DESC;
```

## 11. Troubleshooting

### Queue/Async Jobs Not Working
Audits currently create records but don't process async. To implement:
```bash
# 1. Create job
php artisan make:job ProcessAuditJob

# 2. Dispatch in AuditController
dispatch(new ProcessAuditJob($audit));

# 3. Run queue worker
php artisan queue:work
```

### Stripe Integration
Currently stores Stripe IDs but doesn't process payments. To implement:
1. Add Stripe PHP SDK: `composer require stripe/stripe-php`
2. Create payment endpoint
3. Add webhook handlers
4. Update subscription creation flow

### Sending Emails
Notifications are stored but not sent. To implement:
1. Configure mail driver in `.env`
2. Create mailable classes
3. Dispatch jobs from controllers

## 12. Project Structure

```
app/
├── Http/Controllers/Api/       # API controllers
├── Models/                      # Eloquent models (17 total)
└── Policies/                    # Authorization policies

database/
├── migrations/                  # 17 table migrations
├── factories/                   # Test data factories
└── seeders/                     # Database seeders

routes/
└── api.php                      # API routes (v1)

Documentation/
├── API_DOCUMENTATION.md         # Full API reference
├── DATABASE_SCHEMA.md           # Database design
└── IMPLEMENTATION_SUMMARY.md    # This implementation
```

## 13. Next Steps

1. **Test All Endpoints**: Use the cURL examples provided
2. **Review Models**: Check `app/Models/` to understand data structure
3. **Check Migrations**: Review `database/migrations/` for schema
4. **Implement Features**: Add audit processing, email sending, etc.
5. **Deploy**: Configure `.env` for production and deploy

## 14. Useful Commands

```bash
# Check migrations
php artisan migrate:status

# Rollback last batch
php artisan migrate:rollback

# Fresh migration (drop all tables and migrate)
php artisan migrate:fresh

# Seed specific table
php artisan db:seed --class=SubscriptionPlanSeeder

# Make new controller
php artisan make:controller Api/NewController

# Make new model with migration and factory
php artisan make:model NewModel -mf

# Clear all caches
php artisan cache:clear && php artisan view:clear
```

---

**Need Help?** Check `API_DOCUMENTATION.md` for complete reference.
