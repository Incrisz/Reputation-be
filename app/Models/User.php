<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'company',
        'industry',
        'location',
        'avatar_url',
        'email_verified_at',
        'two_factor_enabled',
        'two_factor_secret',
        'last_login_at',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'two_factor_enabled' => 'boolean',
            'password' => 'hashed',
        ];
    }

    // Relationships
    public function subscription()
    {
        return $this->hasOne(Subscription::class);
    }

    public function businesses()
    {
        return $this->hasMany(Business::class);
    }

    public function audits()
    {
        return $this->hasMany(Audit::class);
    }

    public function usageRecords()
    {
        return $this->hasMany(UsageRecord::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function notificationPreferences()
    {
        return $this->hasOne(NotificationPreference::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }
}
