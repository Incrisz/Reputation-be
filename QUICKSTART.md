# 🚀 Quick Start Guide - BizVisibility AI

Get the Business Visibility Audit API running in under 5 minutes!

## ⚡ Prerequisites

- PHP 8.2 or higher
- Composer
- OpenAI API key (optional, but recommended for AI recommendations)

## 📦 Installation (1 minute)

### 1. Install Dependencies
```bash
composer install
```

### 2. Configure Environment
```bash
# Copy environment file (if not already done)
cp .env.example .env

# Generate application key (if needed)
php artisan key:generate
```

### 3. Add OpenAI API Key (Optional)
Edit `.env` and add your OpenAI API key:
```env
OPENAI_API_KEY=sk-your-openai-api-key-here
```

**Note:** The API works without this key but will use fallback recommendations instead of AI-generated ones.

## 🎯 Run the API (30 seconds)

### Start the Server
```bash
php artisan serve
```

The server will start at: **http://localhost:8000**

## 🧪 Test the API (3 ways)

### Option 1: Swagger UI (Recommended) ⭐

1. Open your browser and navigate to:
   ```
   http://localhost:8000/api/documentation
   ```

2. Click on **POST /api/audit/run**

3. Click the **"Try it out"** button

4. Use this sample data:
   ```json
   {
     "website_url": "https://www.apple.com",
     "business_name": "Apple Inc",
     "industry": "Technology",
     "country": "United States",
     "city": "Cupertino",
     "target_audience": "Consumers and professionals seeking premium technology products",
     "keywords": ["iPhone", "MacBook"]
   }
   ```

5. Click **"Execute"**

6. Review the comprehensive audit results!

### Option 2: cURL Command

```bash
curl -X POST http://localhost:8000/api/audit/run \
  -H "Content-Type: application/json" \
  -d '{
    "website_url": "https://www.apple.com",
    "business_name": "Apple Inc",
    "industry": "Technology",
    "country": "United States",
    "city": "Cupertino",
    "target_audience": "Consumers and professionals seeking premium products",
    "keywords": ["iPhone", "MacBook"]
  }'
```

### Option 3: Postman

1. Import the provided collection:
   - File: `postman_collection.json`

2. Run the "Run Business Audit" request

## 📊 What You'll Get

The API will return a comprehensive audit including:

- **Website Score** (0-100): Technical SEO, content quality, security, UX
- **Social Media Score** (0-100): Platform presence and consistency
- **Overall Grade**: A+ to F with actionable insights
- **AI Recommendations**: Personalized strategies from GPT-4
- **Detailed Findings**: Broken links, meta tags, SSL status, social profiles, and more

### Sample Response (Abbreviated)

```json
{
  "success": true,
  "message": "Audit completed successfully",
  "scores": {
    "website_score": 87,
    "social_media_score": 14,
    "overall_score": 50,
    "grade": "D Below Average"
  },
  "audit_results": {
    "website_audit": {
      "technical_seo": {
        "robots_txt_present": true,
        "sitemap_xml_present": true,
        "broken_links": []
      },
      "content_quality": {
        "word_count": 1937,
        "images_with_alt": 84,
        "images_without_alt": 0
      },
      "security_trust": {
        "ssl_valid": true,
        "privacy_policy_present": true
      }
    },
    "social_media_presence": {
      "platforms": {
        "facebook": { "present": true },
        "instagram": { "present": false },
        "twitter_x": { "present": false }
      },
      "total_platforms_detected": 1
    }
  },
  "ai_recommendations": {
    "success": true,
    "recommendations": "# Detailed AI recommendations..."
  },
  "execution_time": "7.46 seconds"
}
```

## 🎯 Required Fields

The API requires these fields in the request:

| Field | Type | Description | Example |
|-------|------|-------------|---------|
| `website_url` | string (URL) | Business website URL | `https://example.com` |
| `business_name` | string | Official business name | `Acme Corp` |
| `industry` | string | Industry/category | `Digital Marketing` |
| `country` | string | Country of operation | `United States` |
| `city` | string | Primary city | `San Francisco` |
| `target_audience` | string | Target customer description | `Small businesses` |

## 🔧 Optional Fields

| Field | Type | Description | Example |
|-------|------|-------------|---------|
| `competitors` | array | Competitor URLs | `["https://comp1.com"]` |
| `keywords` | array | Target keywords | `["SEO", "marketing"]` |

## 📖 API Endpoint

```
POST http://localhost:8000/api/audit/run
Content-Type: application/json
```

## ⚙️ Configuration

### Environment Variables

Key variables in `.env`:

```env
APP_URL=http://localhost:8000
APP_DEBUG=true
OPENAI_API_KEY=your-key-here

# No database needed - using array drivers
SESSION_DRIVER=array
CACHE_STORE=array
QUEUE_CONNECTION=sync
```

### Swagger Configuration

The Swagger UI is pre-configured and accessible at:
- **URL**: `http://localhost:8000/api/documentation`
- **OpenAPI Spec**: `storage/api-docs/api-docs.json`

## 🛠️ Troubleshooting

### Issue: "Connection refused"
**Solution:** Make sure the server is running:
```bash
php artisan serve
```

### Issue: "OpenAI API error"
**Solution:** Either:
1. Add valid `OPENAI_API_KEY` to `.env`
2. Or ignore - the API will use fallback recommendations

### Issue: "Audit taking too long"
**Cause:** Slow website response or network issues
**Solution:** Normal execution is 5-15 seconds. Wait a bit longer.

### Issue: "Class not found"
**Solution:** Regenerate autoload files:
```bash
composer dump-autoload
```

### Issue: "Route not found"
**Solution:** Clear route cache:
```bash
php artisan route:clear
php artisan config:clear
```

## 📚 Next Steps

1. ✅ **Test with your own website** - Replace the sample data with your business info
2. ✅ **Review the detailed audit results** - Understand all the checks being performed
3. ✅ **Add OpenAI key** - Get AI-powered recommendations
4. ✅ **Read the full README** - Understand the architecture and scoring system
5. ✅ **Explore the code** - Check out the service classes in `app/Services/Audit/`

## 🔗 Important Links

- **Swagger UI**: http://localhost:8000/api/documentation
- **Full README**: [README.md](README.md)
- **Implementation Details**: [IMPLEMENTATION_SUMMARY.md](IMPLEMENTATION_SUMMARY.md)
- **Postman Collection**: [postman_collection.json](postman_collection.json)

## 🎉 You're Ready!

Your Business Visibility Audit API is now running. Test it with different websites and see the comprehensive audit reports with scores and AI recommendations!

---

**Need help?** Check the [README.md](README.md) for detailed documentation or [IMPLEMENTATION_SUMMARY.md](IMPLEMENTATION_SUMMARY.md) for technical details.
