<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subscription_plan_id',
        'status',
        'billing_cycle',
        'current_period_start',
        'current_period_end',
        'trial_ends_at',
        'stripe_customer_id',
        'stripe_subscription_id',
        'stripe_payment_method_id',
        'price',
        'renewal_at',
        'canceled_at',
    ];

    protected $casts = [
        'current_period_start' => 'date',
        'current_period_end' => 'date',
        'trial_ends_at' => 'datetime',
        'renewal_at' => 'datetime',
        'canceled_at' => 'datetime',
        'price' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function usageRecords()
    {
        return $this->hasMany(UsageRecord::class);
    }

    public function stripeEvents()
    {
        return $this->hasMany(StripeEvent::class);
    }
}
