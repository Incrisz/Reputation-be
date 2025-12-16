# BizVisibility AI - Business Reputation & Visibility Audit API

This platform uses AI to evaluate how visible a business is online, from its website to search results and social platforms. It ranks the business and provides intelligent, easy-to-understand recommendations to help it grow digitally.

Our AI-powered app checks how easy it is to find a business online and how well it is presented. It scores the business and tells the owner exactly what to improve to get better visibility and more customers.

## 🚀 Overview

A Laravel-based backend API that provides comprehensive business visibility audits through a **single controller endpoint**, fully testable via **Swagger UI**.

### Key Features

- ✅ **Single Controller Architecture** - All audit logic in one controller
- ✅ **No Database Required** - Stateless, pure API
- ✅ **No Authentication** - Open API for proof-of-concept
- ✅ **Manual Scraping** - No external SEO/social APIs
- ✅ **AI-Powered Recommendations** - GPT-4 integration
- ✅ **Comprehensive Swagger Documentation** - Full OpenAPI spec
- ✅ **Real-time Auditing** - On-demand execution

## 📋 Audit Modules

### 1. Website Audit & SEO On-Site Analysis
- **Technical SEO**: Broken links, robots.txt, sitemap.xml
- **Content Quality**: Word count, meta tags, image alt text
- **Local SEO**: NAP consistency, location keywords
- **Security & Trust**: SSL, privacy policy, terms pages
- **UX & Accessibility**: Mobile viewport, lazy loading
- **Indexability**: Meta robots, canonical tags
- **Brand Consistency**: Business name, logo, favicon

### 2. Social Media Detection
- Detects presence on: Facebook, Instagram, X (Twitter), LinkedIn, TikTok
- Profile validation and completeness checks
- Cross-platform consistency analysis
- Website ↔ social linking verification

### 3. Google Business Profile Detection
- Business listing detection via search simulation
- Profile completeness assessment
- NAP consistency verification
- Trust signals analysis

### 4. AI-Powered Recommendations (GPT-4)
- SEO improvement strategies
- Online visibility optimization
- Social media growth tactics
- Content strategy recommendations
- Quick wins and long-term roadmap

## 🛠️ Tech Stack

- **Framework**: Laravel 12
- **PHP**: 8.2+
- **HTTP Client**: Guzzle
- **API Documentation**: L5-Swagger (OpenAPI 3.0)
- **AI Integration**: OpenAI GPT-4 API

## ⚙️ Installation

### 1. Clone and Install Dependencies

```bash
cd /home/cloud/Videos/Reputation-be
composer install
```

### 2. Configure Environment

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Configure OpenAI (Optional)

Edit `.env` and configure your OpenAI settings:

```env
OPENAI_API_KEY=sk-your-api-key-here
OPENAI_MODEL=gpt-4o
```

**Available Models:**
- `gpt-4o` - Recommended (fast, cost-effective, excellent quality)
- `gpt-4-turbo-preview` - Default (balanced speed and quality)
- `gpt-4o-mini` - Budget option (fastest, cheapest)
- `gpt-3.5-turbo` - Ultra-budget (very cheap, good quality)

**Note**: The API works without an OpenAI key - it will use comprehensive fallback recommendations. See [OPENAI_CONFIGURATION.md](OPENAI_CONFIGURATION.md) for detailed setup.

### 4. Generate Swagger Documentation

```bash
php artisan l5-swagger:generate
```

### 5. Start Development Server

```bash
php artisan serve
```

The API will be available at `http://localhost:8000`

## 📖 API Documentation

### Access Swagger UI

Once the server is running, access the interactive API documentation:

```
http://localhost:8000/api/documentation
```

### Endpoint

**POST** `/api/audit/run`

Executes a comprehensive business visibility audit including website analysis, social media detection, Google Business Profile check, and AI recommendations.

### Request Body

```json
{
  "website_url": "https://example.com",
  "business_name": "Acme Digital Solutions",
  "industry": "Digital Marketing",
  "country": "United States",
  "city": "San Francisco",
  "target_audience": "Small to medium-sized businesses looking to improve their digital presence",
  "competitors": [
    "https://competitor1.com",
    "https://competitor2.com"
  ],
  "keywords": [
    "digital marketing",
    "SEO services",
    "social media management"
  ]
}
```

### Required Fields
- `website_url` - Full URL of the business website
- `business_name` - Official business name
- `industry` - Business industry or category
- `country` - Country where business operates
- `city` - Primary city of operation
- `target_audience` - Description of target customer base

### Optional Fields
- `competitors` - Array of competitor website URLs
- `keywords` - Array of target keywords

### Response Structure

```json
{
  "success": true,
  "message": "Audit completed successfully",
  "input": {
    "website_url": "https://example.com",
    "business_name": "Acme Digital Solutions",
    ...
  },
  "audit_results": {
    "website_audit": {
      "technical_seo": {...},
      "content_quality": {...},
      "local_seo": {...},
      "security_trust": {...},
      "ux_accessibility": {...},
      "indexability": {...},
      "brand_consistency": {...}
    },
    "social_media_presence": {
      "platforms": {
        "facebook": {...},
        "instagram": {...},
        "twitter_x": {...},
        "linkedin": {...},
        "tiktok": {...}
      },
      "total_platforms_detected": 3,
      "cross_platform_consistency": {...}
    },
    "google_business_profile": {
      "listing_detected": {...},
      "business_identity": {...},
      "profile_completeness": {...},
      "location_signals": {...},
      "trust_signals": {...}
    }
  },
  "scores": {
    "website_score": 75,
    "social_media_score": 60,
    "google_business_score": 50,
    "overall_score": 62,
    "grade": "C Average"
  },
  "ai_recommendations": {
    "success": true,
    "recommendations": "# Detailed AI-generated recommendations...",
    "model_used": "gpt-4-turbo-preview",
    "tokens_used": {...}
  },
  "execution_time": "15.32 seconds",
  "timestamp": "2025-12-16T10:30:45+00:00"
}
```

## 🧪 Testing via Swagger UI

1. Navigate to `http://localhost:8000/api/documentation`
2. Click on **POST /api/audit/run**
3. Click **"Try it out"**
4. Fill in the request body with your test data
5. Click **"Execute"**
6. Review the comprehensive audit results below

### Sample Test Data

```json
{
  "website_url": "https://www.apple.com",
  "business_name": "Apple Inc",
  "industry": "Technology",
  "country": "United States",
  "city": "Cupertino",
  "target_audience": "Consumers and professionals seeking premium technology products",
  "keywords": ["iPhone", "MacBook", "consumer electronics"]
}
```

## 🏗️ Architecture

### Single Controller Design

All audit logic is consolidated in [app/Http/Controllers/AuditController.php](app/Http/Controllers/AuditController.php):

1. **Input Validation** - via `BusinessAuditRequest`
2. **Website Audit** - `WebsiteAuditService`
3. **Social Media Detection** - `SocialMediaAuditService`
4. **Google Business Detection** - `GoogleBusinessAuditService`
5. **Score Calculation** - Internal controller methods
6. **AI Recommendations** - `OpenAIService` (GPT-4)
7. **Response Assembly** - Structured JSON output

### Service Classes

```
app/Services/Audit/
├── WebsiteAuditService.php      # Website SEO & technical checks
├── SocialMediaAuditService.php  # Social platform detection
├── GoogleBusinessAuditService.php # GBP detection (simulated)
└── OpenAIService.php            # GPT-4 integration
```

## 📊 Scoring System

### Website Score (0-100)
- Technical SEO: 20 points
- Content Quality: 20 points
- Security & Trust: 20 points
- UX & Accessibility: 15 points
- Indexability: 15 points
- Brand Consistency: 10 points

### Social Media Score (0-100)
- Platform presence: 70 points (14 per platform)
- Cross-platform consistency: 30 points

### Overall Grade
- A+ (90-100): Excellent
- A (80-89): Good
- B (70-79): Above Average
- C (60-69): Average
- D (50-59): Below Average
- F (0-49): Poor

## 🔧 Configuration

### Swagger Configuration

Edit `config/l5-swagger.php` to customize:
- API title and description
- Server URLs
- Documentation path
- Annotation scan paths

### OpenAI Configuration

Edit `config/services.php`:

```php
'openai' => [
    'api_key' => env('OPENAI_API_KEY'),
],
```

## 🚨 Important Notes

### Google Business Profile Detection

The current implementation **simulates** Google search results because:
- Google actively blocks automated scraping
- Production implementation would require:
  - Rotating proxies
  - Rate limiting
  - CAPTCHA solving
  - Or Google Places API (excluded per requirements)

The API returns structured placeholders showing what would be detected in production.

### SSL Certificate Verification

For development purposes, SSL verification is disabled in Guzzle clients. **Enable verification in production**:

```php
'verify' => true,  // In production
```

## 📝 Development

### Regenerate Swagger Docs

After modifying controller annotations:

```bash
php artisan l5-swagger:generate
```

### Clear Cache

```bash
php artisan config:clear
php artisan cache:clear
```

### Check Routes

```bash
php artisan route:list --path=api
```

## 🎯 Use Cases

1. **Web Agencies**: Audit client websites before onboarding
2. **SEO Consultants**: Generate comprehensive SEO reports
3. **Business Owners**: Self-assessment of online presence
4. **Marketing Teams**: Competitive analysis and tracking
5. **Sales Teams**: Lead qualification and needs assessment

## 📚 API Response Examples

### Successful Audit

```json
{
  "success": true,
  "message": "Audit completed successfully",
  "scores": {
    "overall_score": 68,
    "grade": "C Average"
  },
  "execution_time": "12.45 seconds"
}
```

### Validation Error

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "website_url": ["The website URL field is required."]
  }
}
```

### Server Error

```json
{
  "success": false,
  "message": "Audit failed",
  "error": "Connection timeout"
}
```

## 🤝 Contributing

This is a proof-of-concept implementation. For production use, consider:

1. Implementing proper Google Business Profile scraping or API integration
2. Adding rate limiting and caching
3. Implementing authentication/authorization
4. Adding database logging for audit history
5. Enhancing error handling and retry logic
6. Adding more comprehensive test coverage

## 📄 License

MIT License

## 🔗 Resources

- [Laravel Documentation](https://laravel.com/docs)
- [L5-Swagger Documentation](https://github.com/DarkaOnLine/L5-Swagger)
- [OpenAI API Documentation](https://platform.openai.com/docs)
- [OpenAPI Specification](https://swagger.io/specification/)

---

**Built with Laravel 12 & AI-Powered Intelligence** 🚀
