<?php

namespace Database\Factories;

use App\Models\WebsiteAuditFinding;
use App\Models\WebsiteAudit;
use Illuminate\Database\Eloquent\Factories\Factory;

class WebsiteAuditFindingFactory extends Factory
{
    protected $model = WebsiteAuditFinding::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['issue', 'strength']);
        $category = $this->faker->randomElement(['seo', 'performance', 'accessibility', 'security', 'mobile', 'content']);

        return [
            'website_audit_id' => WebsiteAudit::factory(),
            'category' => $category,
            'type' => $type,
            'finding' => $this->getFinding($category, $type),
            'description' => $this->faker->paragraph(),
            'severity' => $type === 'issue' ? $this->faker->randomElement(['critical', 'high', 'medium', 'low']) : null,
        ];
    }

    private function getFinding(string $category, string $type): string
    {
        $issues = [
            'seo' => ['Missing meta descriptions', 'Poor keyword optimization', 'Broken internal links'],
            'performance' => ['Large image files', 'Unminified CSS/JS', 'Slow server response'],
            'accessibility' => ['Missing alt text', 'Poor color contrast', 'Missing form labels'],
            'security' => ['Missing SSL certificate', 'Outdated libraries', 'Exposed sensitive data'],
            'mobile' => ['Not mobile responsive', 'Text too small', 'Buttons too small'],
            'content' => ['Duplicate content', 'Outdated information', 'Poor readability'],
        ];

        $strengths = [
            'seo' => ['Good mobile optimization', 'Fast page load times', 'Proper heading hierarchy'],
            'performance' => ['Optimized images', 'Minified CSS and JS', 'Good caching strategy'],
            'accessibility' => ['Good color contrast', 'Proper heading structure', 'Keyboard navigation'],
            'security' => ['Valid SSL certificate', 'Security headers present', 'No known vulnerabilities'],
            'mobile' => ['Mobile responsive design', 'Fast mobile load', 'Touch-friendly interface'],
            'content' => ['Fresh content', 'Clear messaging', 'Good readability'],
        ];

        $list = $type === 'issue' ? $issues[$category] : $strengths[$category];
        return $list[array_rand($list)];
    }

    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'issue',
            'severity' => 'critical',
        ]);
    }

    public function strength(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'strength',
            'severity' => null,
        ]);
    }
}
