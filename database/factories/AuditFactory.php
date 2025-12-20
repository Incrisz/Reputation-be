<?php

namespace Database\Factories;

use App\Models\Audit;
use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditFactory extends Factory
{
    protected $model = Audit::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'business_id' => Business::factory(),
            'overall_score' => $this->faker->numberBetween(40, 95),
            'execution_time_ms' => $this->faker->numberBetween(2000, 8000),
            'model_used' => $this->faker->randomElement(['gpt-4', 'gpt-4-turbo', 'claude-3-sonnet']),
            'metadata' => [
                'ip_address' => $this->faker->ipv4(),
                'user_agent' => $this->faker->userAgent(),
                'audit_type' => 'manual',
                'ai_model_tokens' => $this->faker->numberBetween(500, 2000),
            ],
        ];
    }

    public function highScore(): static
    {
        return $this->state(fn (array $attributes) => [
            'overall_score' => $this->faker->numberBetween(80, 100),
        ]);
    }

    public function lowScore(): static
    {
        return $this->state(fn (array $attributes) => [
            'overall_score' => $this->faker->numberBetween(30, 50),
        ]);
    }
}
