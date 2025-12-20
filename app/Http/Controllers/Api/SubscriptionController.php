<?php

namespace App\Http\Controllers\Api;

use App\Models\ActivityLog;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;

/**
 * @group Subscriptions
 * APIs for managing subscriptions and plans
 */
class SubscriptionController extends BaseController
{
    /**
     * List all subscription plans
     *
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Free",
     *       "slug": "free",
     *       "price_monthly": 0,
     *       "price_annual": 0,
     *       "audits_per_month": 5,
     *       "businesses_limit": 1,
     *       "features": {}
     *     },
     *     {
     *       "id": 2,
     *       "name": "Pro",
     *       "slug": "pro",
     *       "price_monthly": 2999,
     *       "price_annual": 29990,
     *       "audits_per_month": 50,
     *       "businesses_limit": 5,
     *       "features": {}
     *     }
     *   ]
     * }
     */
    public function plans()
    {
        $plans = SubscriptionPlan::where('active', true)->get();

        return $this->success($plans);
    }

    /**
     * Get current user subscription
     *
     * @authenticated
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "user_id": 1,
     *     "subscription_plan_id": 1,
     *     "status": "active",
     *     "billing_cycle": "monthly",
     *     "current_period_start": "2025-01-20T00:00:00Z",
     *     "current_period_end": "2025-02-20T00:00:00Z",
     *     "price": 29.99,
     *     "plan": {
     *       "id": 1,
     *       "name": "Pro",
     *       "audits_per_month": 50,
     *       "businesses_limit": 5
     *     }
     *   }
     * }
     */
    public function current(Request $request)
    {
        $subscription = $request->user()->subscription->load('plan');

        return $this->success($subscription);
    }

    /**
     * Upgrade or downgrade subscription
     *
     * @authenticated
     * @bodyParam subscription_plan_id int required The plan ID to upgrade/downgrade to. Example: 2
     * @bodyParam billing_cycle string The billing cycle (monthly/annual). Example: monthly
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Subscription updated successfully",
     *   "data": {}
     * }
     */
    public function upgrade(Request $request)
    {
        $validated = $request->validate([
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'billing_cycle' => 'nullable|in:monthly,annual',
        ]);

        $newPlan = SubscriptionPlan::findOrFail($validated['subscription_plan_id']);
        $subscription = $request->user()->subscription;

        // Calculate new price based on billing cycle
        $billingCycle = $validated['billing_cycle'] ?? $subscription->billing_cycle;
        $price = $billingCycle === 'annual' ? $newPlan->price_annual : $newPlan->price_monthly;

        // Update subscription
        $subscription->update([
            'subscription_plan_id' => $newPlan->id,
            'billing_cycle' => $billingCycle,
            'price' => $price / 100,
            'current_period_start' => now(),
            'current_period_end' => $billingCycle === 'annual' ? now()->addYears(1) : now()->addMonths(1),
            'renewal_at' => $billingCycle === 'annual' ? now()->addYears(1) : now()->addMonths(1),
        ]);

        // Log activity
        $oldPlan = SubscriptionPlan::find($subscription->subscription_plan_id);
        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'plan_upgraded',
            'description' => 'Changed subscription from ' . ($oldPlan->name ?? 'Unknown') . ' to ' . $newPlan->name,
            'resource_type' => 'subscription',
            'resource_id' => $subscription->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $this->success($subscription->load('plan'), 'Subscription updated successfully');
    }

    /**
     * Cancel subscription
     *
     * @authenticated
     * @response 200 {
     *   "success": true,
     *   "message": "Subscription canceled successfully"
     * }
     */
    public function cancel(Request $request)
    {
        $subscription = $request->user()->subscription;

        $subscription->update([
            'status' => 'canceled',
            'canceled_at' => now(),
        ]);

        // Log activity
        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'plan_canceled',
            'description' => 'Canceled subscription',
            'resource_type' => 'subscription',
            'resource_id' => $subscription->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $this->success(null, 'Subscription canceled successfully');
    }

    /**
     * Get subscription usage for current period
     *
     * @authenticated
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "audits_used": 15,
     *     "audits_limit": 50,
     *     "audits_percent": 30,
     *     "businesses_used": 3,
     *     "businesses_limit": 5,
     *     "businesses_percent": 60,
     *     "period_start": "2025-01-20T00:00:00Z",
     *     "period_end": "2025-02-20T00:00:00Z"
     *   }
     * }
     */
    public function usage(Request $request)
    {
        $subscription = $request->user()->subscription;
        $plan = $subscription->plan;
        $usageRecord = $subscription->usageRecords()
            ->where('period_start', '<=', now())
            ->where('period_end', '>=', now())
            ->first();

        if (!$usageRecord) {
            $usageRecord = collect([
                'audit_count' => 0,
                'businesses_count' => 0,
                'period_start' => now()->startOfMonth(),
                'period_end' => now()->endOfMonth(),
            ]);
        }

        $auditsLimit = $plan->audits_per_month === 0 ? 999999 : $plan->audits_per_month;
        $businessesLimit = $plan->businesses_limit === 0 ? 999999 : $plan->businesses_limit;

        return $this->success([
            'audits_used' => $usageRecord->audit_count ?? 0,
            'audits_limit' => $auditsLimit,
            'audits_percent' => round((($usageRecord->audit_count ?? 0) / $auditsLimit) * 100),
            'businesses_used' => $usageRecord->businesses_count ?? 0,
            'businesses_limit' => $businessesLimit,
            'businesses_percent' => round((($usageRecord->businesses_count ?? 0) / $businessesLimit) * 100),
            'period_start' => $subscription->current_period_start,
            'period_end' => $subscription->current_period_end,
        ]);
    }
}
