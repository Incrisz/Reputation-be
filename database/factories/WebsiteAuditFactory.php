<?php

namespace Database\Factories;

use App\Models\WebsiteAudit;
use App\Models\Audit;
use Illuminate\Database\Eloquent\Factories\Factory;

class WebsiteAuditFactory extends Factory
{
    protected $model = WebsiteAudit::class;

    public function definition(): array
    {
        return [
            'audit_id' => Audit::factory(),
            'technical_seo_score' => $this->faker->numberBetween(40, 95),
            'content_quality_score' => $this->faker->numberBetween(40, 95),
        ];
    }

    public function highScore(): static
    {
        return $this->state(fn (array $attributes) => [
            'technical_seo_score' => $this->faker->numberBetween(80, 100),
            'content_quality_score' => $this->faker->numberBetween(80, 100),
        ]);
    }

    public function lowScore(): static
    {
        return $this->state(fn (array $attributes) => [
            'technical_seo_score' => $this->faker->numberBetween(30, 50),
            'content_quality_score' => $this->faker->numberBetween(30, 50),
        ]);
    }
}
