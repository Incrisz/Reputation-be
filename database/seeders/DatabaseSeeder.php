<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Audit;
use App\Models\AiRecommendation;
use App\Models\Business;
use App\Models\GoogleBusinessProfile;
use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\SocialMediaProfile;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\WebsiteAudit;
use App\Models\WebsiteAuditFinding;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed subscription plans first (they're needed by subscriptions)
        $this->call(SubscriptionPlanSeeder::class);

        // Delete existing test user if it exists
        User::where('email', 'test@example.com')->delete();

        // Create a single test user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'phone' => '+1-555-0123',
            'company' => 'Test Company',
            'industry' => 'Technology',
            'location' => 'New York, USA',
            'status' => 'active',
            'email_verified_at' => now(),
            'last_login_at' => now(),
        ]);

        // Assign Free plan to user
        $freePlan = SubscriptionPlan::where('slug', 'free')->first();
        
        Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $freePlan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonths(1),
            'renewal_at' => now()->addMonths(1),
            'stripe_customer_id' => 'cus_test_' . $user->id,
            'stripe_subscription_id' => 'sub_test_' . $user->id,
            'price' => 0,
        ]);

        // Create notification preferences
        NotificationPreference::create([
            'user_id' => $user->id,
            'email_notifications_enabled' => true,
            'audit_completion_alerts' => true,
            'weekly_summary' => true,
            'monthly_reports' => true,
            'recommendation_alerts' => true,
        ]);

        // Create a sample business
        $business = Business::create([
            'user_id' => $user->id,
            'website_url' => 'https://www.example.com',
            'business_name' => 'Example Business',
            'industry' => 'Technology',
            'country' => 'USA',
            'city' => 'New York',
            'description' => 'A sample business for testing',
            'keywords' => ['technology', 'web', 'software'],
            'status' => 'active',
            'last_audited_at' => null,
        ]);

        // Create a sample audit
        $audit = Audit::create([
            'user_id' => $user->id,
            'business_id' => $business->id,
            'overall_score' => 85,
            'execution_time_ms' => 3500,
            'model_used' => 'gpt-4-turbo',
            'metadata' => [
                'status' => 'completed',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Test Client',
            ],
        ]);

        // Create website audit with findings
        $websiteAudit = WebsiteAudit::create([
            'audit_id' => $audit->id,
            'technical_seo_score' => 80,
            'content_quality_score' => 90,
        ]);

        // Create sample findings
        WebsiteAuditFinding::create([
            'website_audit_id' => $websiteAudit->id,
            'category' => 'seo',
            'type' => 'issue',
            'finding' => 'Missing meta descriptions',
            'description' => 'Some pages are missing meta descriptions which impacts SEO',
            'severity' => 'high',
        ]);

        WebsiteAuditFinding::create([
            'website_audit_id' => $websiteAudit->id,
            'category' => 'performance',
            'type' => 'strength',
            'finding' => 'Good mobile optimization',
            'description' => 'Website is well optimized for mobile devices',
            'severity' => 'low',
        ]);

        // Create social media profiles
        SocialMediaProfile::create([
            'audit_id' => $audit->id,
            'platform' => 'facebook',
            'url' => 'https://facebook.com/example',
            'presence_detected' => true,
            'linked_from_website' => true,
            'profile_quality_estimate' => 85,
            'followers_estimate' => 5000,
            'verified' => false,
        ]);

        SocialMediaProfile::create([
            'audit_id' => $audit->id,
            'platform' => 'linkedin',
            'url' => 'https://linkedin.com/company/example',
            'presence_detected' => true,
            'linked_from_website' => true,
            'profile_quality_estimate' => 90,
            'followers_estimate' => 2000,
            'verified' => true,
        ]);

        // Create Google Business Profile data
        GoogleBusinessProfile::create([
            'audit_id' => $audit->id,
            'detected' => true,
            'listing_quality_score' => 88,
            'nap_consistency' => 95,
            'review_count' => 42,
            'rating' => 4.8,
            'complete_profile' => true,
            'profile_url' => 'https://www.google.com/business/profile/example',
        ]);

        // Create AI recommendations
        AiRecommendation::create([
            'audit_id' => $audit->id,
            'category' => 'seo',
            'priority' => 'high',
            'recommendation' => 'Add meta descriptions to all pages',
            'implementation_effort' => 'easy',
            'impact_level' => 'high',
            'tokens_used' => 150,
            'model_used' => 'gpt-4-turbo',
        ]);

        AiRecommendation::create([
            'audit_id' => $audit->id,
            'category' => 'social_media',
            'priority' => 'medium',
            'recommendation' => 'Post more frequently on Instagram',
            'implementation_effort' => 'moderate',
            'impact_level' => 'medium',
            'tokens_used' => 100,
            'model_used' => 'gpt-4-turbo',
        ]);

        // Create activity log
        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'audit_completed',
            'description' => 'Completed audit for Example Business',
            'resource_type' => 'audit',
            'resource_id' => $audit->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Client',
        ]);

        // Mark business as audited
        $business->update(['last_audited_at' => now()]);
    }
}
