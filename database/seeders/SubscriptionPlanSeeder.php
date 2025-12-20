<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing plans (disable foreign key checks for truncate)
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('subscription_plans')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Get started with basic website audits',
                'price_monthly' => 0,
                'price_annual' => 0,
                'audits_per_month' => 5,
                'businesses_limit' => 1,
                'history_retention_days' => 30,
                'white_label' => false,
                'support_level' => 'community',
                'features' => json_encode([
                    'website_audit' => true,
                    'social_media_detection' => true,
                    'google_business_profile' => true,
                    'basic_recommendations' => true,
                    'email_reports' => false,
                    'api_access' => false,
                    'team_collaboration' => false,
                ]),
                'stripe_price_id_monthly' => null,
                'stripe_price_id_annual' => null,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'Perfect for small businesses and freelancers',
                'price_monthly' => 2999,
                'price_annual' => 29990,
                'audits_per_month' => 50,
                'businesses_limit' => 5,
                'history_retention_days' => 90,
                'white_label' => false,
                'support_level' => 'email',
                'features' => json_encode([
                    'website_audit' => true,
                    'social_media_detection' => true,
                    'google_business_profile' => true,
                    'basic_recommendations' => true,
                    'advanced_recommendations' => true,
                    'email_reports' => true,
                    'pdf_reports' => true,
                    'audit_comparison' => true,
                    'api_access' => false,
                    'team_collaboration' => false,
                ]),
                'stripe_price_id_monthly' => 'price_pro_monthly',
                'stripe_price_id_annual' => 'price_pro_annual',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'description' => 'For growing businesses with advanced needs',
                'price_monthly' => 9999,
                'price_annual' => 99990,
                'audits_per_month' => 0,
                'businesses_limit' => 25,
                'history_retention_days' => 365,
                'white_label' => true,
                'support_level' => 'priority',
                'features' => json_encode([
                    'website_audit' => true,
                    'social_media_detection' => true,
                    'google_business_profile' => true,
                    'basic_recommendations' => true,
                    'advanced_recommendations' => true,
                    'ai_recommendations' => true,
                    'email_reports' => true,
                    'pdf_reports' => true,
                    'audit_comparison' => true,
                    'api_access' => true,
                    'team_collaboration' => true,
                    'custom_branding' => true,
                ]),
                'stripe_price_id_monthly' => 'price_business_monthly',
                'stripe_price_id_annual' => 'price_business_annual',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Custom solution for large organizations',
                'price_monthly' => 29999,
                'price_annual' => 299990,
                'audits_per_month' => 0,
                'businesses_limit' => 0,
                'history_retention_days' => 0,
                'white_label' => true,
                'support_level' => '24/7',
                'features' => json_encode([
                    'website_audit' => true,
                    'social_media_detection' => true,
                    'google_business_profile' => true,
                    'basic_recommendations' => true,
                    'advanced_recommendations' => true,
                    'ai_recommendations' => true,
                    'email_reports' => true,
                    'pdf_reports' => true,
                    'audit_comparison' => true,
                    'api_access' => true,
                    'team_collaboration' => true,
                    'custom_branding' => true,
                    'dedicated_account_manager' => true,
                    'sso_integration' => true,
                    'custom_integrations' => true,
                ]),
                'stripe_price_id_monthly' => 'price_enterprise_monthly',
                'stripe_price_id_annual' => 'price_enterprise_annual',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('subscription_plans')->insert($plans);
    }
}
