<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BusinessFactory extends Factory
{
    protected $model = Business::class;

    public function definition(): array
    {
        $domain = $this->faker->domainName();

        return [
            'user_id' => User::factory(),
            'website_url' => 'https://www.' . $domain,
            'business_name' => $this->faker->company(),
            'industry' => $this->faker->randomElement(['Technology', 'Healthcare', 'Finance', 'Retail', 'Manufacturing', 'Services', 'Hospitality', 'Education']),
            'country' => $this->faker->country(),
            'city' => $this->faker->city(),
            'description' => $this->faker->paragraph(),
            'keywords' => [
                $this->faker->word(),
                $this->faker->word(),
                $this->faker->word(),
            ],
            'logo_url' => 'https://logo.clearbit.com/' . $domain,
            'status' => 'active',
            'last_audited_at' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    public function audited(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_audited_at' => now(),
        ]);
    }
}
