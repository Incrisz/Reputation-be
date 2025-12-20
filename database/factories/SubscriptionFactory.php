<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        $plan = SubscriptionPlan::inRandomOrder()->first() ?? SubscriptionPlan::factory();
        $currentPeriodStart = now();
        $currentPeriodEnd = now()->addMonths(1);

        return [
            'user_id' => User::factory(),
            'subscription_plan_id' => $plan,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'current_period_start' => $currentPeriodStart,
            'current_period_end' => $currentPeriodEnd,
            'trial_ends_at' => null,
            'stripe_customer_id' => 'cus_' . $this->faker->unique()->bothify('??????????'),
            'stripe_subscription_id' => 'sub_' . $this->faker->unique()->bothify('??????????'),
            'stripe_payment_method_id' => 'pm_' . $this->faker->unique()->bothify('??????????'),
            'price' => $plan->price_monthly / 100,
            'renewal_at' => $currentPeriodEnd,
            'canceled_at' => null,
        ];
    }

    public function withTrial(): static
    {
        return $this->state(fn (array $attributes) => [
            'trial_ends_at' => now()->addDays(14),
        ]);
    }

    public function canceled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'canceled',
            'canceled_at' => now(),
        ]);
    }

    public function annual(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_cycle' => 'annual',
            'current_period_end' => now()->addYears(1),
            'renewal_at' => now()->addYears(1),
        ]);
    }
}
