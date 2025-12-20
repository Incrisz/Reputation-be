<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Audit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_id',
        'overall_score',
        'execution_time_ms',
        'model_used',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function websiteAudit()
    {
        return $this->hasOne(WebsiteAudit::class);
    }

    public function websiteAuditFindings()
    {
        return $this->hasManyThrough(WebsiteAuditFinding::class, WebsiteAudit::class);
    }

    public function socialMediaProfiles()
    {
        return $this->hasMany(SocialMediaProfile::class);
    }

    public function googleBusinessProfile()
    {
        return $this->hasOne(GoogleBusinessProfile::class);
    }

    public function aiRecommendations()
    {
        return $this->hasMany(AiRecommendation::class);
    }

    public function auditReports()
    {
        return $this->hasMany(AuditReport::class);
    }

    public function comparisons()
    {
        return $this->hasMany(AuditComparison::class, 'audit_id_1');
    }
}
