# BizVisibility AI - Complete Database Schema Documentation

**Last Updated**: December 20, 2025  
**Project**: BizVisibility AI SaaS Platform  
**Database Type**: MySQL/MariaDB  
**Framework**: Laravel 12

---

## 📋 Table of Contents

1. [Overview](#overview)
2. [Core Architecture](#core-architecture)
3. [Tables Specification](#tables-specification)
4. [Relationships](#relationships)
5. [Indexes](#indexes)
6. [Design Decisions](#design-decisions)

---

## 🎯 Overview

### Business Context
BizVisibility AI is a multi-tenant SaaS platform that provides AI-powered business reputation and visibility audits. The database is designed to support:

- **Multi-tenant architecture** with user accounts and subscriptions
- **Subscription-based pricing** with Stripe integration (Free, Pro, Business, Enterprise)
- **Usage tracking** for quota enforcement per plan
- **Audit management** with normalized data storage
- **Report generation** and sharing capabilities
- **Activity logging** for security and compliance

### Key Features
- ✅ Users manage multiple businesses
- ✅ Run on-demand audits per business
- ✅ Store detailed audit results in normalized tables
- ✅ Track social media profiles per audit
- ✅ AI-generated recommendations per audit
- ✅ Generate and share PDF reports
- ✅ Stripe payment integration
- ✅ Usage tracking for quota enforcement
- ✅ Activity audit trail for security

### Design Principles
- **Normalized Storage**: Break down JSON audit results into separate tables
- **Stripe Integration**: Store Stripe customer and subscription IDs
- **Usage Tracking**: Monthly reset based on billing cycle
- **No Soft Deletes**: Hard deletes only (simplified deletion logic)
- **No Audit Scheduling**: On-demand audits only (future enhancement)

---

## 🏗️ Core Architecture

### Data Flow

```
User (Signup/Login)
  ↓
Subscription (Plan Selection + Stripe Payment)
  ↓
Business (Add website to audit)
  ↓
Audit (Run audit on business website)
  ↓
Audit Results
  ├─ Website Audit Details
  ├─ Website Audit Findings (Issues & Strengths)
  ├─ Social Media Profiles
  ├─ Google Business Profile
  └─ AI Recommendations
  ↓
Audit Report (Generate PDF)
  ↓
Share Report (Email/Download)
```

### User Journey

1. **User Registration** → `users` table
2. **Select Plan** → `subscriptions` table + Stripe webhook
3. **Add Business** → `businesses` table
4. **Run Audit** → `audits` table + normalized sub-tables
5. **View Results** → Query audit + all related tables
6. **Generate Report** → `audit_reports` table
7. **Track Usage** → `usage_records` table (check limits)

---

## 📊 Tables Specification

### 1. USERS Table

**Purpose**: Store user account information and authentication details

```sql
CREATE TABLE users (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  phone VARCHAR(20) NULLABLE,
  company VARCHAR(255) NULLABLE,
  industry VARCHAR(100) NULLABLE,
  location VARCHAR(255) NULLABLE,
  avatar_url VARCHAR(500) NULLABLE,
  email_verified_at TIMESTAMP NULLABLE,
  two_factor_enabled BOOLEAN DEFAULT FALSE,
  two_factor_secret VARCHAR(255) NULLABLE,
  last_login_at TIMESTAMP NULLABLE,
  status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
  remember_token VARCHAR(100) NULLABLE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INDEX idx_email (email);
INDEX idx_status (status);
INDEX idx_created_at (created_at);
```

**Fields Explanation**:
- `name`: User's full name
- `email`: Unique email for login
- `password`: Hashed password
- `phone`: Contact number
- `company`: Company/agency name
- `industry`: Business industry type
- `location`: User's location
- `avatar_url`: Profile picture URL
- `email_verified_at`: Email verification timestamp
- `two_factor_enabled`: 2FA activation flag
- `two_factor_secret`: Encrypted 2FA secret for TOTP
- `last_login_at`: Last login timestamp for analytics
- `status`: Account status (active/inactive/suspended)
- `remember_token`: "Remember me" token for sessions

---

### 2. SUBSCRIPTION_PLANS Table

**Purpose**: Define available subscription tiers (lookup/reference table)

```sql
CREATE TABLE subscription_plans (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL UNIQUE,
  slug VARCHAR(50) NOT NULL UNIQUE,
  description TEXT NULLABLE,
  price_monthly DECIMAL(8, 2) DEFAULT 0,
  price_annual DECIMAL(8, 2) DEFAULT 0,
  audits_per_month INT DEFAULT 0,
  businesses_limit INT DEFAULT 0,
  history_retention_days INT DEFAULT 0,
  white_label BOOLEAN DEFAULT FALSE,
  support_level ENUM('basic', 'priority', 'dedicated') DEFAULT 'basic',
  features JSON NULLABLE,
  stripe_price_id_monthly VARCHAR(255) NULLABLE,
  stripe_price_id_annual VARCHAR(255) NULLABLE,
  active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INDEX idx_slug (slug);
INDEX idx_active (active);
```

**Predefined Plans**:
1. **Free**: 5 audits/month, 1 business, 7-day history, basic support
2. **Pro**: 50 audits/month, 5 businesses, 90-day history, priority support
3. **Business**: Unlimited audits, unlimited businesses, unlimited history, priority support, 

**Fields Explanation**:
- `audits_per_month`: Monthly limit (0 = unlimited)
- `businesses_limit`: Max businesses (0 = unlimited)
- `history_retention_days`: How long to retain audit history (0 = unlimited)
- `features`: JSON array of feature flags
- `stripe_price_id_*`: Stripe Price IDs for billing

---

### 3. SUBSCRIPTIONS Table

**Purpose**: Track active subscription per user with Stripe integration

```sql
CREATE TABLE subscriptions (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL UNIQUE,
  subscription_plan_id BIGINT UNSIGNED NOT NULL,
  status ENUM('active', 'past_due', 'canceled', 'trial') DEFAULT 'active',
  billing_cycle ENUM('monthly', 'annual') DEFAULT 'monthly',
  current_period_start DATE NOT NULL,
  current_period_end DATE NOT NULL,
  trial_ends_at TIMESTAMP NULLABLE,
  stripe_customer_id VARCHAR(255) NOT NULL UNIQUE,
  stripe_subscription_id VARCHAR(255) NULLABLE UNIQUE,
  stripe_payment_method_id VARCHAR(255) NULLABLE,
  price DECIMAL(8, 2) NOT NULL,
  renewal_at TIMESTAMP NULLABLE,
  canceled_at TIMESTAMP NULLABLE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (subscription_plan_id) REFERENCES subscription_plans(id)
);

INDEX idx_user_id (user_id);
INDEX idx_stripe_customer_id (stripe_customer_id);
INDEX idx_stripe_subscription_id (stripe_subscription_id);
INDEX idx_status (status);
INDEX idx_current_period_end (current_period_end);
```

**Fields Explanation**:
- `status`: Subscription state management
- `current_period_start/end`: Billing cycle dates
- `trial_ends_at`: Free trial expiration (if applicable)
- `stripe_customer_id`: Stripe customer reference
- `stripe_subscription_id`: Stripe subscription reference
- `stripe_payment_method_id`: Default payment method
- `renewal_at`: Next renewal date
- `canceled_at`: When subscription was canceled

---

### 4. USAGE_RECORDS Table

**Purpose**: Track monthly usage for quota enforcement

```sql
CREATE TABLE usage_records (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  subscription_id BIGINT UNSIGNED NOT NULL,
  audit_count INT DEFAULT 0,
  api_calls_count INT DEFAULT 0,
  businesses_count INT DEFAULT 0,
  period_start DATE NOT NULL,
  period_end DATE NOT NULL,
  reset_date DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE
);

INDEX idx_user_id_period (user_id, period_start, period_end);
INDEX idx_reset_date (reset_date);
UNIQUE INDEX idx_user_period (user_id, period_start, period_end);
```

**Purpose**: 
- Check if user has exceeded monthly audit limit
- Reset counters automatically on period_end
- Calculate usage percentage for dashboard

**Fields Explanation**:
- `audit_count`: Number of audits run this month
- `api_calls_count`: API calls made this month
- `businesses_count`: Current number of active businesses
- `period_start/end`: Billing cycle dates
- `reset_date`: When counters reset (automated)

---

### 5. BUSINESSES Table

**Purpose**: Store websites/businesses that users audit

```sql
CREATE TABLE businesses (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  website_url VARCHAR(500) NOT NULL,
  business_name VARCHAR(255) NOT NULL,
  industry VARCHAR(100) NULLABLE,
  country VARCHAR(100) NULLABLE,
  city VARCHAR(100) NULLABLE,
  description TEXT NULLABLE,
  keywords JSON NULLABLE,
  logo_url VARCHAR(500) NULLABLE,
  status ENUM('active', 'inactive') DEFAULT 'active',
  last_audited_at TIMESTAMP NULLABLE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

INDEX idx_user_id (user_id);
INDEX idx_website_url (website_url);
INDEX idx_user_website (user_id, website_url);
INDEX idx_created_at (created_at);
UNIQUE INDEX idx_user_business (user_id, website_url);
```

**Fields Explanation**:
- `website_url`: Main domain to audit
- `business_name`: Business/company name
- `industry`: Industry classification
- `country/city`: Business location
- `keywords`: JSON array of relevant keywords
- `logo_url`: Business logo for reports
- `last_audited_at`: When the latest audit was run
- **Note**: Plan limits enforced via `usage_records.businesses_count`

---

### 6. AUDITS Table

**Purpose**: Main audit record linking to all audit details

```sql
CREATE TABLE audits (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  business_id BIGINT UNSIGNED NOT NULL,
  overall_score INT DEFAULT 0,
  execution_time_ms INT DEFAULT 0,
  model_used VARCHAR(100) NULLABLE,
  metadata JSON NULLABLE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
);

INDEX idx_user_id (user_id);
INDEX idx_business_id (business_id);
INDEX idx_user_created (user_id, created_at DESC);
INDEX idx_business_created (business_id, created_at DESC);
```

**Fields Explanation**:
- `overall_score`: 0-100 score combining all categories
- `execution_time_ms`: How long the audit took (for performance tracking)
- `model_used`: Which AI model was used (gpt-4o, gpt-4o-mini, etc.)
- `metadata`: Additional data (API response time, tokens used, etc.)

**Data Relationships**:
- One audit → One `website_audits` record
- One audit → Multiple `website_audit_findings` records
- One audit → Multiple `social_media_profiles` records
- One audit → One `google_business_profiles` record
- One audit → Multiple `ai_recommendations` records
- One audit → Multiple `audit_reports` records

---

### 7. WEBSITE_AUDITS Table

**Purpose**: Store technical SEO and website analysis scores

```sql
CREATE TABLE website_audits (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  audit_id BIGINT UNSIGNED NOT NULL UNIQUE,
  technical_seo_score INT DEFAULT 0,
  content_quality_score INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (audit_id) REFERENCES audits(id) ON DELETE CASCADE
);

INDEX idx_audit_id (audit_id);
```

**Score Breakdown**:
- `technical_seo_score`: SSL, robots.txt, sitemap.xml, mobile-friendly
- `content_quality_score`: Meta tags, word count, heading structure, keywords

---

### 8. WEBSITE_AUDIT_FINDINGS Table

**Purpose**: Store individual issues and strengths from website audit

```sql
CREATE TABLE website_audit_findings (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  website_audit_id BIGINT UNSIGNED NOT NULL,
  category VARCHAR(100) NOT NULL,
  type ENUM('issue', 'strength') NOT NULL,
  finding VARCHAR(255) NOT NULL,
  description TEXT NULLABLE,
  severity ENUM('critical', 'high', 'medium', 'low') DEFAULT 'medium',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (website_audit_id) REFERENCES website_audits(id) ON DELETE CASCADE
);

INDEX idx_website_audit_id (website_audit_id);
INDEX idx_category_type (category, type);
INDEX idx_severity (severity);
```

**Category Values**:
- `technical_seo`
- `content_quality`


**Example Findings**:
- Issue: "Missing robots.txt" (technical_seo, critical)
- Strength: "Valid SSL certificate" (security_trust)
- Issue: "No mobile viewport meta tag" (ux_accessibility, high)

---

### 9. SOCIAL_MEDIA_PROFILES Table

**Purpose**: Store detected social media presence per audit

```sql
CREATE TABLE social_media_profiles (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  audit_id BIGINT UNSIGNED NOT NULL,
  platform VARCHAR(50) NOT NULL,
  url VARCHAR(500) NULLABLE,
  presence_detected BOOLEAN DEFAULT FALSE,
  linked_from_website BOOLEAN DEFAULT FALSE,
  profile_quality_estimate VARCHAR(50) NULLABLE,
  followers_estimate INT DEFAULT 0,
  verified BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (audit_id) REFERENCES audits(id) ON DELETE CASCADE
);

INDEX idx_audit_id (audit_id);
INDEX idx_platform (platform);
UNIQUE INDEX idx_audit_platform (audit_id, platform);
```

**Platform Values**:
- `facebook`
- `instagram`
- `twitter` (X)
- `linkedin`
- `tiktok`
- `youtube`
- `google_business`

**Fields Explanation**:
- `presence_detected`: True if profile found via web search
- `linked_from_website`: True if website links to profile
- `profile_quality_estimate`: poor, fair, good, excellent
- `followers_estimate`: Approximate follower count
- `verified`: Blue checkmark status

---

### 10. GOOGLE_BUSINESS_PROFILES Table

**Purpose**: Track Google Business Profile detection and metrics

```sql
CREATE TABLE google_business_profiles (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  audit_id BIGINT UNSIGNED NOT NULL UNIQUE,
  detected BOOLEAN DEFAULT FALSE,
  listing_quality_score INT DEFAULT 0,
  nap_consistency VARCHAR(50) NULLABLE,
  review_count INT DEFAULT 0,
  rating DECIMAL(2, 1) DEFAULT 0,
  complete_profile BOOLEAN DEFAULT FALSE,
  profile_url VARCHAR(500) NULLABLE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (audit_id) REFERENCES audits(id) ON DELETE CASCADE
);

INDEX idx_audit_id (audit_id);
```

**Fields Explanation**:
- `detected`: GBP found via search simulation
- `listing_quality_score`: How complete the GBP is
- `nap_consistency`: Name/Address/Phone consistency (perfect, good, poor)
- `review_count`: Total reviews on GBP
- `rating`: Average rating (1.0-5.0)
- `complete_profile`: All required fields filled out

---

### 11. AI_RECOMMENDATIONS Table

**Purpose**: Store AI-generated recommendations for improvement

```sql
CREATE TABLE ai_recommendations (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  audit_id BIGINT UNSIGNED NOT NULL,
  category VARCHAR(100) NOT NULL,
  priority VARCHAR(20) NOT NULL,
  recommendation TEXT NOT NULL,
  implementation_effort VARCHAR(20) NULLABLE,
  impact_level VARCHAR(20) NULLABLE,
  tokens_used INT DEFAULT 0,
  model_used VARCHAR(100) NULLABLE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (audit_id) REFERENCES audits(id) ON DELETE CASCADE
);

INDEX idx_audit_id (audit_id);
INDEX idx_category_priority (category, priority);
```

**Category Values**:
- `seo`
- `online_presence`
- `social_media`
- `content`
- `brand_visibility`
- `quick_wins`
- `long_term_roadmap`

**Priority Values**: `critical`, `high`, `medium`, `low`

**Impact Level**: `low`, `medium`, `high`

**Implementation Effort**: `easy`, `medium`, `hard`

**Example**:
```
category: "seo"
priority: "high"
recommendation: "Create and submit a sitemap.xml to Google Search Console for better crawlability"
impact_level: "high"
implementation_effort: "easy"
```

---

### 12. AUDIT_REPORTS Table

**Purpose**: Track generated PDF and shareable reports

```sql
CREATE TABLE audit_reports (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  audit_id BIGINT UNSIGNED NOT NULL,
  report_type VARCHAR(50) NOT NULL,
  file_path VARCHAR(500) NULLABLE,
  file_size INT DEFAULT 0,
  share_token VARCHAR(100) UNIQUE NULLABLE,
  share_expires_at TIMESTAMP NULLABLE,
  download_count INT DEFAULT 0,
  shared_with_count INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  expires_at TIMESTAMP NULLABLE,
  
  FOREIGN KEY (audit_id) REFERENCES audits(id) ON DELETE CASCADE
);

INDEX idx_audit_id (audit_id);
INDEX idx_share_token (share_token);
INDEX idx_created_at (created_at);
```

**Report Types**:
- `pdf` - Full PDF report
- `html` - Shareable HTML report
- `email` - Email report sent

**Fields Explanation**:
- `file_path`: Storage path (/storage/reports/audit-id.pdf)
- `share_token`: Unique token for shareable links
- `download_count`: How many times downloaded
- `expires_at`: Auto-delete old reports

---

### 13. AUDIT_COMPARISONS Table

**Purpose**: Store side-by-side audit comparisons for trend analysis

```sql
CREATE TABLE audit_comparisons (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  audit_id_1 BIGINT UNSIGNED NOT NULL,
  audit_id_2 BIGINT UNSIGNED NOT NULL,
  score_improvement INT DEFAULT 0,
  key_improvements JSON NULLABLE,
  areas_declined JSON NULLABLE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (audit_id_1) REFERENCES audits(id) ON DELETE CASCADE,
  FOREIGN KEY (audit_id_2) REFERENCES audits(id) ON DELETE CASCADE
);

INDEX idx_audit_1 (audit_id_1);
INDEX idx_audit_2 (audit_id_2);
INDEX idx_created_at (created_at);
```

**Fields Explanation**:
- `score_improvement`: Difference in overall_score
- `key_improvements`: JSON array of areas that improved
- `areas_declined`: JSON array of areas that got worse

**Example**:
```json
{
  "score_improvement": 12,
  "key_improvements": ["Added sitemap.xml", "Fixed mobile responsiveness"],
  "areas_declined": ["Reduced social media updates"]
}
```

---

### 14. NOTIFICATION_PREFERENCES Table

**Purpose**: Store user notification settings

```sql
CREATE TABLE notification_preferences (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL UNIQUE,
  email_notifications_enabled BOOLEAN DEFAULT TRUE,
  audit_completion_alerts BOOLEAN DEFAULT TRUE,
  weekly_summary BOOLEAN DEFAULT TRUE,
  monthly_reports BOOLEAN DEFAULT TRUE,
  recommendation_alerts BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

INDEX idx_user_id (user_id);
```

---

### 15. NOTIFICATIONS Table

**Purpose**: Log of sent notifications

```sql
CREATE TABLE notifications (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  type VARCHAR(100) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  data JSON NULLABLE,
  read_at TIMESTAMP NULLABLE,
  sent_at TIMESTAMP NULLABLE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

INDEX idx_user_id (user_id);
INDEX idx_type (type);
INDEX idx_read_at (read_at);
INDEX idx_created_at (created_at);
```

**Notification Types**:
- `audit_completed`: Audit finished running
- `weekly_digest`: Weekly summary email
- `monthly_report`: Monthly report generation
- `plan_expiring`: Subscription expiring soon
- `payment_failed`: Payment card declined
- `recommendation_alert`: Important recommendations

---

### 16. ACTIVITY_LOGS Table

**Purpose**: Security and compliance audit trail

```sql
CREATE TABLE activity_logs (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NULLABLE,
  action VARCHAR(100) NOT NULL,
  description TEXT NULLABLE,
  resource_type VARCHAR(100) NULLABLE,
  resource_id BIGINT UNSIGNED NULLABLE,
  ip_address VARCHAR(45) NULLABLE,
  user_agent VARCHAR(500) NULLABLE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

INDEX idx_user_id (user_id);
INDEX idx_action (action);
INDEX idx_created_at (created_at);
INDEX idx_resource (resource_type, resource_id);
```

**Action Values**:
- `user_login`
- `user_logout`
- `user_signup`
- `password_changed`
- `profile_updated`
- `business_created`
- `business_deleted`
- `audit_started`
- `audit_completed`
- `report_generated`
- `report_shared`
- `plan_upgraded`
- `plan_downgraded`
- `payment_failed`

---

### 17. STRIPE_EVENTS Table

**Purpose**: Log Stripe webhook events for idempotency

```sql
CREATE TABLE stripe_events (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  event_id VARCHAR(255) NOT NULL UNIQUE,
  event_type VARCHAR(100) NOT NULL,
  user_id BIGINT UNSIGNED NULLABLE,
  subscription_id BIGINT UNSIGNED NULLABLE,
  data JSON NOT NULL,
  processed BOOLEAN DEFAULT FALSE,
  processed_at TIMESTAMP NULLABLE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE SET NULL
);

INDEX idx_event_id (event_id);
INDEX idx_event_type (event_type);
INDEX idx_processed (processed);
INDEX idx_created_at (created_at);
```

**Event Types**:
- `customer.subscription.created`
- `customer.subscription.updated`
- `customer.subscription.deleted`
- `invoice.payment_succeeded`
- `invoice.payment_failed`
- `customer.updated`
- `payment_method.attached`

---

## 🔗 Relationships

### Entity Relationship Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                          USERS                              │
│  (id, name, email, password, company, industry, location)  │
└──────────────┬──────────────────────────────────────────────┘
               │
       ┌───────┴───────┬──────────────────┐
       │               │                  │
       ▼               ▼                  ▼
   ┌────────┐   ┌──────────────┐   ┌──────────────┐
   │SUBSCR. │   │BUSINESSES    │   │NOTIFICATIONS│
   └────────┘   └──────────────┘   └──────────────┘
       │               │
       │               ▼
       │          ┌──────────┐
       │          │AUDITS    │
       │          └────┬─────┘
       │               │
       │       ┌───────┴───────────┬──────────────┬──────────────┐
       │       │                   │              │              │
       ▼       ▼                   ▼              ▼              ▼
    ┌──────┐┌──────────────┐┌──────────────┐┌──────────────┐┌──────────┐
    │USAGE││WEBSITE_AUDITS││SOCIAL_MEDIA_││GOOGLE_BUS_   ││AI_RECOM. │
    │RECS ││              ││PROFILES     ││PROFILES     ││          │
    └──────┘└──────┬───────┘└──────────────┘└──────────────┘└──────────┘
                  │
                  ▼
            ┌──────────────────┐
            │WEBSITE_AUDIT_    │
            │FINDINGS          │
            └──────────────────┘
```

### Detailed Relationships

```
users (1) ──── (1) subscriptions
users (1) ──── (many) businesses
users (1) ──── (many) audits
users (1) ──── (many) usage_records
users (1) ──── (many) notifications
users (1) ──── (many) activity_logs

subscriptions (1) ──── (many) subscription_plans
subscriptions (1) ──── (many) usage_records

businesses (1) ──── (many) audits

audits (1) ──── (1) website_audits
audits (1) ──── (many) website_audit_findings
audits (1) ──── (many) social_media_profiles
audits (1) ──── (1) google_business_profiles
audits (1) ──── (many) ai_recommendations
audits (1) ──── (many) audit_reports
audits (1) ──── (many) audit_comparisons

website_audits (1) ──── (many) website_audit_findings
```

---

## 📈 Indexes

### Primary Indexes (Performance Critical)

```
-- User lookups
users.email (UNIQUE)
users.status (for filtering active users)

-- Business queries
businesses.user_id
businesses.user_id + website_url (UNIQUE)
businesses.created_at (for ordering)

-- Audit queries (MOST IMPORTANT)
audits.user_id + created_at DESC (recent audits)
audits.business_id + created_at DESC (business history)

-- Usage tracking
usage_records.user_id + period_start + period_end (UNIQUE)

-- Report sharing
audit_reports.share_token (UNIQUE)

-- Activity trail
activity_logs.user_id + created_at DESC
activity_logs.action (for filtering)

-- Stripe integration
subscriptions.stripe_customer_id (UNIQUE)
subscriptions.stripe_subscription_id (UNIQUE)
stripe_events.event_id (UNIQUE)
stripe_events.processed (for webhook processing)
```

### Query Optimization Indexes

```
-- Social media detection
social_media_profiles.audit_id + platform (UNIQUE)

-- Finding categorization
website_audit_findings.website_audit_id
website_audit_findings.category + type

-- Notifications
notifications.user_id + read_at (unread count)
notifications.created_at (for pagination)

-- Recommendations by priority
ai_recommendations.audit_id
ai_recommendations.category + priority

-- History retention cleanup
audit_reports.expires_at (for auto-delete jobs)
```

---

## 🎯 Design Decisions

### 1. Normalized Audit Storage

**Decision**: Break down audit JSON into separate tables instead of storing single JSON blob

**Rationale**:
- ✅ Easier to query specific audit components
- ✅ Better for filtering (e.g., "show all audits with score > 80")
- ✅ Better for reporting and analytics
- ✅ Easier to index specific fields
- ❌ Slightly more complex schema
- ❌ More writes per audit

**Tables affected**: `website_audits`, `website_audit_findings`, `social_media_profiles`, `google_business_profiles`, `ai_recommendations`

---

### 2. Stripe Payment Integration

**Decision**: Store Stripe customer ID and subscription ID, not full payment details

**Rationale**:
- ✅ PCI DSS compliant (don't store card data)
- ✅ Stripe handles encryption and security
- ✅ Can retrieve payment history from Stripe API
- ✅ Webhooks update our DB with payment status
- ❌ Requires Stripe API calls for payment history

**Implementation**:
- Store `stripe_customer_id` in subscriptions
- Listen for Stripe webhooks in `stripe_events`
- Update subscription status from webhooks
- Query Stripe API for detailed payment history

---

### 3. Usage Tracking

**Decision**: Monthly reset based on `subscription.current_period_end`, tracked in separate table

**Rationale**:
- ✅ Can enforce quotas before allowing audits
- ✅ Per-plan limits enforced easily
- ✅ Easy to reset each month automatically
- ✅ Clear audit trail of usage patterns

**Implementation**:
- On `subscription.current_period_end`, run job to reset `usage_records`
- Before each audit, check: `usage_records.audit_count < plan.audits_per_month`
- Increment `usage_records.audit_count` after successful audit

---

### 4. No Soft Deletes

**Decision**: Hard deletes only (no `deleted_at` column)

**Rationale**:
- ✅ Simpler queries
- ✅ Cleaner data (no deleted clutter)
- ✅ GDPR-compliant (hard delete for "right to be forgotten")
- ❌ Cannot recover deleted data
- ❌ Loss of historical records

**Implementation**:
- Foreign keys with `ON DELETE CASCADE` for related records
- Archive/backup old data before deletion if needed
- Activity logs show what was deleted and when

---

### 5. No Audit Scheduling

**Decision**: Only on-demand audits (no scheduled/recurring audits)

**Rationale**:
- ✅ Simpler initial implementation
- ✅ Reduces background job complexity
- ✅ Meets current business requirements
- ❌ No automated recurring audits (future enhancement)

**Future Enhancement**: Add `scheduled_audits` table when needed

---

### 6. Social Media as Separate Rows

**Decision**: One row per platform per audit (not JSON array)

**Rationale**:
- ✅ Easier to filter (e.g., "which businesses have Twitter")
- ✅ Better for sorting and pagination
- ✅ Easier to add platform-specific fields
- ❌ Requires JOIN queries

**Query Example**:
```sql
SELECT DISTINCT b.business_name, COUNT(*) as platforms
FROM social_media_profiles sp
JOIN audits a ON sp.audit_id = a.id
JOIN businesses b ON a.business_id = b.id
WHERE sp.presence_detected = TRUE
GROUP BY b.id;
```

---

### 7. Activity Logging Everything

**Decision**: Log all important user actions in `activity_logs`

**Rationale**:
- ✅ GDPR compliance and security audits
- ✅ Understand user behavior
- ✅ Detect suspicious activity
- ✅ Troubleshoot issues
- ❌ More disk space

**Logged Actions**: login, signup, audit creation, report generation, payment events, etc.

---

### 8. Audit Comparisons

**Decision**: Store comparison metadata when users compare two audits

**Rationale**:
- ✅ Users see comparison trends
- ✅ Can show "score improved by X%"
- ✅ Can highlight what changed
- ❌ Extra storage

**Usage**: When user clicks "Compare audits", create record or calculate on-the-fly

---

## 🔐 Security Considerations

### Password Security
- Passwords hashed with bcrypt (Laravel default)
- Never transmitted in plain text
- Password reset via email token

### API Token Security
- Tokens stored as hashes (not plain text)
- Hash compared on each API request
- Can be invalidated/revoked anytime
- Rate limiting per token

### Payment Security
- Stripe handles PCI compliance
- Never store credit card data
- Only store Stripe customer/subscription IDs
- Webhook signature verification

### 2FA Security
- Two-factor secret encrypted in database
- TOTP (Time-based One-Time Password) implementation
- Recovery codes generated during setup

### Activity Logging
- Track all sensitive actions
- IP address logging for device tracking
- User agent logging for browser detection
- Helps detect unauthorized access

---

## 📅 Data Retention Policy

| Table | Retention | Rationale |
|-------|-----------|-----------|
| users | Forever | Account data |
| subscriptions | Forever | Billing history |
| businesses | Forever | User data |
| audits | Plan-dependent (7/90/365 days) | Based on plan |
| audit_reports | 2 years | Compliance |
| notifications | 1 year | Audit trail |
| activity_logs | 2 years | Security/compliance |
| stripe_events | 1 year | Payment history |

---

## 🚀 Migration Strategy

The database will be created using Laravel migrations:

1. `0001_create_users_table.php`
2. `0002_create_subscription_plans_table.php`
3. `0003_create_subscriptions_table.php`
4. `0004_create_usage_records_table.php`
5. `0005_create_businesses_table.php`
6. `0006_create_audits_table.php`
7. `0007_create_website_audits_table.php`
8. `0008_create_website_audit_findings_table.php`
9. `0009_create_social_media_profiles_table.php`
10. `0010_create_google_business_profiles_table.php`
11. `0011_create_ai_recommendations_table.php`
12. `0012_create_audit_reports_table.php`
13. `0013_create_audit_comparisons_table.php`
14. `0014_create_notification_preferences_table.php`
15. `0015_create_notifications_table.php`
16. `0016_create_activity_logs_table.php`
17. `0017_create_stripe_events_table.php`

---

## 📝 Example Queries

### Find recent audits for a user
```sql
SELECT a.*, b.business_name
FROM audits a
JOIN businesses b ON a.business_id = b.id
WHERE a.user_id = 1
ORDER BY a.created_at DESC
LIMIT 10;
```

### Check if user exceeded monthly quota
```sql
SELECT 
  ur.audit_count,
  sp.audits_per_month,
  (sp.audits_per_month - ur.audit_count) as remaining
FROM usage_records ur
JOIN subscriptions s ON ur.subscription_id = s.id
JOIN subscription_plans sp ON s.subscription_plan_id = sp.id
WHERE ur.user_id = 1
  AND ur.period_start <= NOW()
  AND ur.period_end >= NOW();
```

### Get all social media profiles for a business
```sql
SELECT DISTINCT platform, COUNT(*) as audit_count
FROM social_media_profiles sp
JOIN audits a ON sp.audit_id = a.id
WHERE a.business_id = 5
  AND sp.presence_detected = TRUE
GROUP BY platform;
```

### Find recommendations by priority
```sql
SELECT category, priority, COUNT(*) as count
FROM ai_recommendations
WHERE audit_id = 123
GROUP BY category, priority
ORDER BY priority;
```

### Track user activity
```sql
SELECT action, COUNT(*) as count, MAX(created_at) as last_action
FROM activity_logs
WHERE user_id = 1
GROUP BY action
ORDER BY count DESC;
```

---

## 🎓 Implementation Notes

### Before Creating Migrations
- [ ] Confirm database type (MySQL)
- [ ] Review table relationships
- [ ] Decide on soft deletes (we chose NO)
- [ ] Plan for indexing strategy
- [ ] Consider cascade delete behavior

### When Running Migrations
- [ ] Run in order (migrations are timestamped)
- [ ] Use `php artisan migrate` for development
- [ ] Use `php artisan migrate --force` for production
- [ ] Test rollback: `php artisan migrate:rollback`

### Post-Migration Steps
- [ ] Create Eloquent models with relationships
- [ ] Create factories for testing
- [ ] Create seeders for subscription plans
- [ ] Test CRUD operations
- [ ] Verify foreign key constraints

---

## 📞 Questions & Support

For questions about this schema:
- Review the relationships section
- Check example queries
- Refer to design decisions
- Test with sample data first

**Last Updated**: December 20, 2025
