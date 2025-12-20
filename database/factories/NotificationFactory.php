<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['audit_completed', 'weekly_digest', 'monthly_report', 'plan_expiring', 'payment_failed', 'recommendation_alert']);

        return [
            'user_id' => User::factory(),
            'type' => $type,
            'subject' => $this->getSubject($type),
            'message' => $this->getMessage($type),
            'data' => [
                'audit_id' => rand(1, 100),
                'business_id' => rand(1, 50),
            ],
            'read_at' => $this->faker->boolean(60) ? now()->subHours(rand(1, 48)) : null,
            'sent_at' => now()->subHours(rand(1, 72)),
        ];
    }

    private function getSubject(string $type): string
    {
        return match ($type) {
            'audit_completed' => 'Your website audit is complete',
            'weekly_digest' => 'Your weekly audit summary',
            'monthly_report' => 'Your monthly performance report',
            'plan_expiring' => 'Your subscription is expiring soon',
            'payment_failed' => 'Payment failed for your subscription',
            'recommendation_alert' => 'New recommendations for your website',
            default => 'Notification',
        };
    }

    private function getMessage(string $type): string
    {
        return match ($type) {
            'audit_completed' => 'Your website audit has been completed. Check the results in your dashboard.',
            'weekly_digest' => 'Here is your weekly digest of website audits and performance metrics.',
            'monthly_report' => 'Your monthly performance report is ready to download.',
            'plan_expiring' => 'Your current subscription plan will expire in 7 days. Please renew to continue.',
            'payment_failed' => 'Your recent payment failed. Please update your payment method.',
            'recommendation_alert' => 'We have new AI-generated recommendations based on your latest audit.',
            default => 'You have a new notification',
        };
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => now(),
        ]);
    }

    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => null,
        ]);
    }
}
