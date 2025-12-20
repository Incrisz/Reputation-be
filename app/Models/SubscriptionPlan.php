<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_monthly',
        'price_annual',
        'audits_per_month',
        'businesses_limit',
        'history_retention_days',
        'white_label',
        'support_level',
        'features',
        'stripe_price_id_monthly',
        'stripe_price_id_annual',
        'active',
    ];

    protected $casts = [
        'features' => 'array',
        'price_monthly' => 'decimal:2',
        'price_annual' => 'decimal:2',
        'white_label' => 'boolean',
        'active' => 'boolean',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
