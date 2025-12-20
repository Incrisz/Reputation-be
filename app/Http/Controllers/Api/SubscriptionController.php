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
     * @OA\Get(
     *     path="/subscription/plans",
     *     operationId="listSubscriptionPlans",
     *     tags={"Subscriptions"},
     *     summary="Get subscription plans",
     *     description="List all available subscription plans (public endpoint)",
     *     @OA\Response(
     *         response=200,
     *         description="Plans retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", items={"type":"object"})
     *         )
     *     )
     * )
     */
    public function plans()
    {
        $plans = SubscriptionPlan::where('active', true)->get();

        return $this->success($plans);
    }

    /**
     * Get current user subscription
     *
     * @OA\Get(
     *     path="/subscription/current",
     *     operationId="getCurrentSubscription",
     *     tags={"Subscriptions"},
     *     summary="Get current subscription",
     *     description="Retrieve user's active subscription",
     *     security={{"bearerToken":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Subscription retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function current(Request $request)
    {
        $subscription = $request->user()->subscription->load('plan');

        return $this->success($subscription);
    }

    /**
     * Upgrade or downgrade subscription
     *
     * @OA\Post(
     *     path="/subscription/upgrade",
     *     operationId="upgradeSubscription",
     *     tags={"Subscriptions"},
     *     summary="Upgrade subscription",
     *     description="Upgrade to a higher tier plan",
     *     security={{"bearerToken":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"subscription_plan_id"},
     *             @OA\Property(property="subscription_plan_id", type="integer", example=2),
     *             @OA\Property(property="billing_cycle", type="string", enum={"monthly","annual"}, example="monthly")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Subscription upgraded",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Subscription updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
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
     * @OA\Post(
     *     path="/subscription/cancel",
     *     operationId="cancelSubscription",
     *     tags={"Subscriptions"},
     *     summary="Cancel subscription",
     *     description="Cancel the active subscription",
     *     security={{"bearerToken":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Subscription cancelled",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Subscription canceled successfully")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
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
     * @OA\Get(
     *     path="/subscription/usage",
     *     operationId="getSubscriptionUsage",
     *     tags={"Subscriptions"},
     *     summary="Get subscription usage",
     *     description="Get current usage statistics against plan limits",
     *     security={{"bearerToken":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Usage retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="audits_used", type="integer"),
     *                 @OA\Property(property="audits_limit", type="integer"),
     *                 @OA\Property(property="audits_percent", type="integer"),
     *                 @OA\Property(property="businesses_used", type="integer"),
     *                 @OA\Property(property="businesses_limit", type="integer"),
     *                 @OA\Property(property="businesses_percent", type="integer"),
     *                 @OA\Property(property="period_start", type="string", format="date-time"),
     *                 @OA\Property(property="period_end", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
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
