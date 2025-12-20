# BizVisibility AI - Website Audit & Reputation Management Platform

## 📋 Overview

BizVisibility AI is a comprehensive SaaS platform for website audits, reputation management, and AI-powered business visibility insights. It provides detailed analysis of website performance, SEO metrics, social media presence, and actionable recommendations for improving online presence.

**Key Features:**
- 🔍 Comprehensive website audits (SEO, performance, accessibility, security)
- 📊 Social media presence detection and analysis
- 🏢 Google Business Profile integration and metrics
- 🤖 AI-powered recommendations using GPT-4
- 📈 Audit comparison and trend analysis
- 📱 Multi-tenant SaaS architecture
- 💳 Stripe subscription billing integration
- 📢 Email notifications and reports
- 🔐 JWT authentication with Sanctum
- 📚 Complete REST API with Swagger documentation

---

## 🛠️ Tech Stack

### Backend
- **Framework:** Laravel 12
- **Language:** PHP 8.3+
- **Database:** MySQL/MariaDB
- **API Documentation:** L5 Swagger (OpenAPI 3.0)
- **Authentication:** Laravel Sanctum (JWT)
- **Queue System:** Redis/Database
- **Email:** Mailable classes with queue support

### Architecture
- RESTful API-first design
- Multi-tenant with user isolation
- Eloquent ORM with relationships
- Database migrations for schema versioning
- Factory and Seeder pattern for testing

### Deployment Ready
- Docker support (PHP, Nginx, MariaDB, Redis)
- Environment configuration
- Health check endpoints
- Error logging and monitoring

---

## 📁 Project Structure

```
/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       ├── AuthController.php          # User authentication
│   │   │       ├── BusinessController.php      # Business/website management
│   │   │       ├── AuditController.php         # Audit management
│   │   │       ├── SubscriptionController.php  # Subscription management
│   │   │       ├── BaseController.php          # Common response methods
│   │   │       └── SwaggerDocumentation.php    # API documentation
│   │   ├── Requests/                           # Validation classes (ready to add)
│   │   └── Middleware/                         # Auth, CORS, etc.
│   ├── Models/
│   │   ├── User.php
│   │   ├── Business.php
│   │   ├── Audit.php
│   │   ├── WebsiteAudit.php
│   │   ├── WebsiteAuditFinding.php
│   │   ├── SocialMediaProfile.php
│   │   ├── GoogleBusinessProfile.php
│   │   ├── AiRecommendation.php
│   │   ├── Subscription.php
│   │   ├── SubscriptionPlan.php
│   │   ├── UsageRecord.php
│   │   ├── Notification.php
│   │   ├── NotificationPreference.php
│   │   ├── ActivityLog.php
│   │   ├── AuditReport.php
│   │   ├── AuditComparison.php
│   │   └── StripeEvent.php
│   ├── Policies/
│   │   ├── BusinessPolicy.php
│   │   └── AuditPolicy.php
│   └── Providers/
│       └── AppServiceProvider.php
├── database/
│   ├── migrations/
│   │   ├── 2025_12_20_000001_create_users_table.php
│   │   ├── 2025_12_20_000002_create_subscription_plans_table.php
│   │   ├── 2025_12_20_000003_create_subscriptions_table.php
│   │   ├── ... (17 migrations total)
│   ├── factories/
│   │   ├── UserFactory.php
│   │   ├── BusinessFactory.php
│   │   ├── AuditFactory.php
│   │   ├── WebsiteAuditFactory.php
│   │   ├── SubscriptionFactory.php
│   │   ├── NotificationFactory.php
│   │   └── WebsiteAuditFindingFactory.php
│   └── seeders/
│       ├── DatabaseSeeder.php
│       └── SubscriptionPlanSeeder.php
├── routes/
│   ├── api.php                              # API routes (v1)
│   └── web.php                              # Web routes + Swagger
├── config/
│   └── l5-swagger.php                       # Swagger configuration
├── storage/
│   └── api-docs/
│       └── api-docs.json                    # Generated OpenAPI spec
├── tests/                                   # Test suite
├── .env.example                             # Environment template
├── setup-db.sh                              # Database setup script
├── API_TESTING_GUIDE.md                     # Complete API testing documentation
├── DATABASE_SCHEMA.md                       # Database design documentation
├── postman_collection.json                  # Postman API collection
└── composer.json                            # PHP dependencies
```

---

## 🚀 Quick Start

### Prerequisites
- PHP 8.3+
- Composer
- MySQL 8.0+
- Node.js 18+ (optional, for frontend)

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/reputation-be.git
   cd reputation-be
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure database in `.env`**
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=bizvisibility
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. **Run setup script**
   ```bash
   chmod +x setup-db.sh
   ./setup-db.sh
   ```

   This will:
   - Run all migrations
   - Seed subscription plans and test data
   - Generate Swagger documentation

6. **Start development server**
   ```bash
   php artisan serve
   ```

   Server runs on: `http://localhost:8000`

7. **Access Swagger Documentation**
   ```
   http://localhost:8000/api/docs
   ```

---

## 📚 Database Schema

### 17 Tables

**Core Tables:**
- `users` - User accounts with authentication
- `subscription_plans` - Available subscription tiers
- `subscriptions` - User subscription records with Stripe integration
- `usage_records` - Monthly quota tracking

**Business & Audits:**
- `businesses` - Websites/businesses for audit
- `audits` - Main audit records
- `website_audits` - Website analysis scores
- `website_audit_findings` - Individual findings and strengths
- `social_media_profiles` - Detected social platforms
- `google_business_profiles` - GBP metrics
- `ai_recommendations` - AI-generated recommendations
- `audit_reports` - Generated PDF/HTML reports
- `audit_comparisons` - Audit comparison records

**User Engagement:**
- `notifications` - Notification logs
- `notification_preferences` - User notification settings
- `activity_logs` - Security audit trail
- `stripe_events` - Webhook event logging

**Key Features:**
- Foreign key constraints with CASCADE deletes
- Composite indexes for performance
- JSON storage for flexible data (features, metadata, etc.)
- Multi-tenant isolation by user_id
- Timestamped for audit trails
- UUID support ready

---

## 🔌 API Endpoints

### Authentication (Public)
- `POST /auth/register` - Register new account
- `POST /auth/login` - Login with credentials
- `POST /auth/logout` - Logout (requires auth)
- `GET /auth/me` - Get current user profile (requires auth)

### Businesses (Auth Required)
- `GET /businesses` - List all businesses (paginated)
- `POST /businesses` - Create new business
- `GET /businesses/{id}` - Get business details
- `PUT /businesses/{id}` - Update business
- `DELETE /businesses/{id}` - Delete business

### Audits (Auth Required)
- `GET /audits` - List all audits (paginated)
- `GET /audits/{id}` - Get audit details with findings
- `POST /audits/trigger` - Start new audit
- `GET /audits/compare` - Compare two audits
- `DELETE /audits/{id}` - Delete audit

### Subscriptions (Public/Auth)
- `GET /subscription/plans` - List all plans (public)
- `GET /subscription/current` - Get current subscription (auth)
- `POST /subscription/upgrade` - Upgrade/downgrade plan (auth)
- `POST /subscription/cancel` - Cancel subscription (auth)
- `GET /subscription/usage` - Get usage statistics (auth)

**Full documentation:** See `API_TESTING_GUIDE.md`

---

## 🔐 Authentication

Uses **Laravel Sanctum** for token-based authentication:

1. Register or login to get a token
2. Include token in `Authorization: Bearer {token}` header
3. Token remains valid until explicitly revoked
4. Tokens can be revoked on logout

```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  http://localhost:8000/api/v1/auth/me
```

---

## 📋 Subscription Plans

| Plan | Price/Month | Audits/Month | Businesses | Features |
|------|-------------|--------------|-----------|----------|
| **Free** | $0 | 5 | 1 | Basic audit, Social detection, GBP detection |
| **Pro** | $29.99 | 50 | 5 | + Email reports, PDF export, Comparisons, Recommendations |
| **Business** | $99.99 | Unlimited | 25 | + API access, Team collaboration, Custom branding, 24/7 support |
| **Enterprise** | Custom | Unlimited | Unlimited | Everything + Dedicated manager, SSO, Custom integrations |

---

## 🗄️ Database Schema Highlights

### Users Table
```sql
- id (primary key)
- name, email (unique), password
- phone, company, industry, location
- avatar_url, email_verified_at
- two_factor_enabled, two_factor_secret
- last_login_at, status (active/inactive/suspended)
- timestamps
```

### Audits Table (Multi-tenant)
```sql
- id (primary key)
- user_id (index) - Isolates data per user
- business_id (foreign key)
- overall_score (0-100)
- execution_time_ms
- model_used (gpt-4, gpt-4-turbo, claude-3-sonnet)
- metadata (JSON) - Flexible additional data
- timestamps
- Indexes: (user_id, created_at DESC), (business_id, created_at DESC)
```

### Website Audits Table
```sql
- id (primary key)
- audit_id (unique) - 1:1 relationship
- technical_seo_score, content_quality_score
- timestamp created_at only
```

### Subscriptions Table
```sql
- id (primary key)
- user_id (unique) - Only one subscription per user
- subscription_plan_id (foreign key)
- status (active/paused/canceled)
- billing_cycle (monthly/annual)
- current_period_start, current_period_end
- trial_ends_at, canceled_at
- stripe_customer_id (unique) - For Stripe integration
- stripe_subscription_id (unique)
- stripe_payment_method_id
- price, renewal_at
```

---

## 🤖 AI Integration

The platform supports multiple AI models for generating recommendations:

- **GPT-4** - Full reasoning and analysis
- **GPT-4 Turbo** - Faster processing, cost-effective
- **Claude 3 Sonnet** - Alternative for recommendations

Model selection configurable per audit in `metadata`.

---

## 📊 Test Data

Database seeders create realistic test data:

- **5 Test Users** with full profiles
- **3 Businesses per user** (different industries)
- **2 Audits per business** with complete results
- **5 Findings per audit** (mix of issues and strengths)
- **6 Social media profiles** per audit (with detection data)
- **Google Business Profile** data
- **3-5 AI recommendations** per audit
- **Activity logs** for security audit trail

---

## 🧪 Testing

### Run Tests
```bash
php artisan test
```

### Test with Swagger UI
1. Navigate to `http://localhost:8000/api/docs`
2. Click "Authorize" and enter your token
3. Try out any endpoint with "Try it out" button

### Test with cURL
```bash
# Register
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"John","email":"john@example.com","password":"SecurePass123!","password_confirmation":"SecurePass123!"}'

# Login
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"john@example.com","password":"SecurePass123!"}'

# Create business
curl -X POST http://localhost:8000/api/v1/businesses \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"website_url":"https://example.com","business_name":"Example Co"}'
```

---

## 🔧 Configuration

### Environment Variables

```env
# App
APP_NAME="BizVisibility AI"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bizvisibility
DB_USERNAME=root
DB_PASSWORD=

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS=noreply@bizvisibility.ai

# Stripe
STRIPE_PUBLIC_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

# Redis (optional)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Queue
QUEUE_CONNECTION=database
```

---

## 🚀 Deployment

### Docker Support

A complete Docker setup is available in the `docker/` directory:

```bash
docker-compose up -d
docker-compose exec php php artisan migrate
docker-compose exec php php artisan db:seed
```

### Manual Deployment

1. Upload code to server
2. Run `composer install --no-dev`
3. Configure `.env` with production values
4. Run migrations: `php artisan migrate --force`
5. Generate app key: `php artisan key:generate`
6. Set proper file permissions: `chmod -R 775 storage bootstrap/cache`
7. Use Nginx/Apache with proper PHP-FPM configuration
8. Set up queue worker: `php artisan queue:work`
9. Set up task scheduler: `* * * * * cd /path/to/app && php artisan schedule:run`

---

## 📚 Documentation

- **API Testing Guide:** `API_TESTING_GUIDE.md` - Complete endpoint documentation with examples
- **Database Schema:** `DATABASE_SCHEMA.md` - Detailed table specifications
- **Swagger UI:** `http://localhost:8000/api/docs` - Interactive API documentation
- **Postman Collection:** `postman_collection.json` - Ready-to-import Postman requests

---

## 🔄 Workflow

### User Journey

1. **Sign Up** → Get Free plan automatically
2. **Create Business** → Add website to audit
3. **Run Audit** → Get comprehensive analysis
4. **View Results** → See scores, findings, recommendations
5. **Compare Audits** → Track improvements over time
6. **Upgrade Plan** → Access advanced features
7. **Export Reports** → Download PDF or share results

### Data Flow

```
User Registration
    ↓
Auto-assign Free Plan + Subscription
    ↓
Create Business Record
    ↓
Trigger Audit
    ↓
Process Audit (background job)
    ↓
Update Audit with Scores/Findings
    ↓
Generate AI Recommendations
    ↓
Send Notification
    ↓
Display Results in Dashboard
```

---

## 🛣️ Roadmap

### Phase 1 (Current) ✅
- [x] Core database schema
- [x] User authentication (Sanctum)
- [x] Business management
- [x] Audit framework
- [x] Subscription management
- [x] API endpoints with Swagger documentation
- [x] Test data seeders
- [x] Activity logging

### Phase 2 (Next)
- [ ] Background audit processing (queue jobs)
- [ ] Stripe webhook integration
- [ ] Email notification service
- [ ] PDF report generation
- [ ] Frontend dashboard (React/Vue)
- [ ] Advanced analytics
- [ ] Team collaboration features

### Phase 3 (Future)
- [ ] API rate limiting and throttling
- [ ] Custom integrations (CRM, marketing platforms)
- [ ] White-label solution
- [ ] Advanced reporting and exports
- [ ] Machine learning for predictive insights
- [ ] Mobile app
- [ ] Multi-language support

---

## 🤝 Contributing

1. Create a feature branch: `git checkout -b feature/your-feature`
2. Make your changes
3. Run tests: `php artisan test`
4. Commit: `git commit -am 'Add your feature'`
5. Push: `git push origin feature/your-feature`
6. Create a pull request

---

## 📞 Support

For issues, feature requests, or questions:
- Email: support@bizvisibility.ai
- Issues: GitHub Issues
- Documentation: See docs folder

---

## 📄 License

Proprietary - BizVisibility AI © 2025

---

## 🙏 Acknowledgments

Built with:
- Laravel 12
- MySQL
- L5 Swagger for OpenAPI documentation
- Laravel Sanctum for authentication
- Eloquent ORM

---

**Happy Auditing! 🚀**
