<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email_notifications_enabled',
        'audit_completion_alerts',
        'weekly_summary',
        'monthly_reports',
        'recommendation_alerts',
    ];

    protected $casts = [
        'email_notifications_enabled' => 'boolean',
        'audit_completion_alerts' => 'boolean',
        'weekly_summary' => 'boolean',
        'monthly_reports' => 'boolean',
        'recommendation_alerts' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
