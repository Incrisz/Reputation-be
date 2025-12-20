<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsageRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subscription_id',
        'audit_count',
        'api_calls_count',
        'businesses_count',
        'period_start',
        'period_end',
        'reset_date',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'reset_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
