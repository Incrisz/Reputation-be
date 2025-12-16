# OpenAI Configuration Guide

This guide explains how to configure the OpenAI API integration for AI-powered business visibility recommendations.

## 🔑 Configuration

The OpenAI integration is now fully configurable via the `.env` file.

### Environment Variables

Edit your [.env](.env) file and configure:

```env
# OpenAI API Configuration
OPENAI_API_KEY=your-api-key-here
OPENAI_MODEL=gpt-4-turbo-preview
```

### Configuration Options

#### 1. OPENAI_API_KEY

**Required for AI recommendations** (optional for testing)

- Get your API key from: https://platform.openai.com/api-keys
- Sign up or log in to your OpenAI account
- Create a new secret key
- Copy and paste it into `.env`

**Example:**
```env
OPENAI_API_KEY=sk-proj-abc123def456ghi789...
```

**Without API Key:**
- The API will work normally
- AI recommendations will use comprehensive fallback content
- All other audit features remain fully functional

#### 2. OPENAI_MODEL

**Configurable model selection** (defaults to `gpt-4-turbo-preview`)

Choose from available OpenAI models based on your needs:

### Available Models

#### GPT-4 Models (Recommended)

| Model | Speed | Cost | Quality | Best For |
|-------|-------|------|---------|----------|
| `gpt-4-turbo-preview` | Fast | Medium | Excellent | **Default - Best balance** |
| `gpt-4o` | Very Fast | Low | Excellent | High-volume audits |
| `gpt-4o-mini` | Fastest | Very Low | Good | Cost optimization |
| `gpt-4-turbo` | Fast | Medium | Excellent | Latest features |
| `gpt-4` | Slow | High | Best | Premium quality |

#### GPT-3.5 Models (Budget Option)

| Model | Speed | Cost | Quality | Best For |
|-------|-------|------|---------|----------|
| `gpt-3.5-turbo` | Very Fast | Very Low | Good | Budget-friendly |
| `gpt-3.5-turbo-16k` | Very Fast | Low | Good | Larger context |

### Model Selection Guide

#### For Production (Recommended)
```env
OPENAI_MODEL=gpt-4o
```
- Fast response times
- Lower cost than GPT-4 Turbo
- Excellent quality recommendations
- **Best for most use cases**

#### For Maximum Quality
```env
OPENAI_MODEL=gpt-4-turbo-preview
```
- Current default
- Balanced speed and quality
- Great for detailed analysis

#### For High-Volume / Budget
```env
OPENAI_MODEL=gpt-4o-mini
```
- Fastest response
- Lowest cost
- Still provides quality recommendations
- **Ideal for 100+ audits per day**

#### For Ultra-Budget
```env
OPENAI_MODEL=gpt-3.5-turbo
```
- Very low cost (~90% cheaper than GPT-4)
- Fast responses
- Adequate quality for basic recommendations

### Cost Estimates

Based on typical audit (1,500-2,000 tokens per request):

| Model | Cost per Audit | 100 Audits | 1,000 Audits |
|-------|----------------|------------|--------------|
| `gpt-4` | $0.06-0.08 | $6-8 | $60-80 |
| `gpt-4-turbo-preview` | $0.02-0.04 | $2-4 | $20-40 |
| `gpt-4o` | $0.01-0.015 | $1-1.50 | $10-15 |
| `gpt-4o-mini` | $0.003-0.005 | $0.30-0.50 | $3-5 |
| `gpt-3.5-turbo` | $0.002-0.003 | $0.20-0.30 | $2-3 |

**Recommended for most users:** `gpt-4o` (great balance)

## ⚙️ Configuration Files

The OpenAI settings are configured in three places:

### 1. Environment File (`.env`)
```env
OPENAI_API_KEY=your-key
OPENAI_MODEL=gpt-4o
```

### 2. Services Configuration ([config/services.php](config/services.php#L38-41))
```php
'openai' => [
    'api_key' => env('OPENAI_API_KEY'),
    'model' => env('OPENAI_MODEL', 'gpt-4-turbo-preview'),
],
```

### 3. Service Class ([app/Services/Audit/OpenAIService.php](app/Services/Audit/OpenAIService.php#L17-18))
```php
$this->apiKey = config('services.openai.api_key', env('OPENAI_API_KEY'));
$this->model = config('services.openai.model', env('OPENAI_MODEL', 'gpt-4-turbo-preview'));
```

## 🧪 Testing Different Models

### Quick Model Switch

1. Edit `.env` file:
```env
OPENAI_MODEL=gpt-4o-mini
```

2. Test immediately (no restart needed):
```bash
curl -X POST http://localhost:8000/api/audit/run \
  -H "Content-Type: application/json" \
  -d '{
    "website_url": "https://example.com",
    "business_name": "Test Business",
    "industry": "Technology",
    "country": "United States",
    "city": "San Francisco",
    "target_audience": "Small businesses"
  }'
```

3. Check the response `model_used` field:
```json
{
  "ai_recommendations": {
    "success": true,
    "model_used": "gpt-4o-mini",
    "recommendations": "..."
  }
}
```

## 📊 Response Format

The AI recommendations are returned in the audit response:

```json
{
  "success": true,
  "ai_recommendations": {
    "success": true,
    "recommendations": "# Detailed markdown recommendations...",
    "model_used": "gpt-4o",
    "tokens_used": {
      "prompt_tokens": 1247,
      "completion_tokens": 823,
      "total_tokens": 2070
    }
  }
}
```

### With API Key (success: true)
- Detailed AI-generated recommendations
- Model name shown in `model_used`
- Token usage statistics
- Personalized insights based on audit data

### Without API Key (success: false)
- Comprehensive fallback recommendations
- Generic but valuable guidance
- All audit features still work

## 🔒 Security Best Practices

### 1. Keep API Key Secret
```bash
# Never commit .env file to Git
echo ".env" >> .gitignore
```

### 2. Use Environment Variables
- Never hardcode API keys in code
- Always use `env('OPENAI_API_KEY')`

### 3. Rotate Keys Regularly
- Generate new API keys periodically
- Revoke old keys from OpenAI dashboard

### 4. Monitor Usage
- Check usage at: https://platform.openai.com/usage
- Set usage limits in OpenAI dashboard
- Monitor costs regularly

## 🚨 Troubleshooting

### Issue: "OpenAI API Error: Unauthorized"
**Cause:** Invalid or missing API key

**Solution:**
1. Check API key in `.env` is correct
2. Verify key is active at https://platform.openai.com/api-keys
3. Ensure no extra spaces in `.env` file

### Issue: "Model not found"
**Cause:** Invalid model name in `OPENAI_MODEL`

**Solution:**
1. Check model name spelling
2. Use one of the supported models listed above
3. Verify model access in your OpenAI account

### Issue: "Rate limit exceeded"
**Cause:** Too many requests

**Solution:**
1. Upgrade OpenAI plan for higher limits
2. Add rate limiting to your API
3. Use slower model (lower tier limits)

### Issue: "Insufficient quota"
**Cause:** OpenAI account out of credits

**Solution:**
1. Add credits to OpenAI account
2. Check billing at https://platform.openai.com/account/billing
3. API will fall back to static recommendations

## 📝 Recommendation Content

The AI generates recommendations in these categories:

1. **SEO Improvements** - Technical SEO, content, on-page optimizations
2. **Online Visibility Strategy** - Overall online presence improvements
3. **Social Media Growth** - Platform-specific strategies
4. **Google Business Profile** - Local search optimization
5. **Content Strategy** - Content creation guidelines
6. **Competitive Positioning** - Differentiation strategies
7. **Quick Wins** - Immediate action items (3-5)
8. **Long-term Strategy** - 3-6 month roadmap

### Fallback Recommendations

When API key is not configured, the service provides:
- Comprehensive generic recommendations
- Industry best practices
- Actionable quick wins
- Long-term strategic guidance

**Quality:** Still valuable and comprehensive, just not personalized to audit data.

## 🎯 Recommendations

### For Development/Testing
```env
# Use fallback (no API key needed)
OPENAI_API_KEY=
OPENAI_MODEL=gpt-4o
```

### For Low-Volume Production
```env
OPENAI_API_KEY=sk-your-key
OPENAI_MODEL=gpt-4o
```

### For High-Volume Production
```env
OPENAI_API_KEY=sk-your-key
OPENAI_MODEL=gpt-4o-mini
```

### For Maximum Quality
```env
OPENAI_API_KEY=sk-your-key
OPENAI_MODEL=gpt-4-turbo-preview
```

## 🔗 Resources

- **OpenAI Platform**: https://platform.openai.com
- **API Keys**: https://platform.openai.com/api-keys
- **Usage Dashboard**: https://platform.openai.com/usage
- **Pricing**: https://openai.com/pricing
- **API Documentation**: https://platform.openai.com/docs

---

**Quick Setup:**
1. Get API key from OpenAI
2. Add to `.env`: `OPENAI_API_KEY=your-key`
3. Choose model: `OPENAI_MODEL=gpt-4o`
4. Test the API immediately (no restart needed)

**Note:** The API works perfectly without OpenAI - all audit features remain functional with comprehensive fallback recommendations.
