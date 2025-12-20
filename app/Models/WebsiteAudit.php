<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebsiteAudit extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'audit_id',
        'technical_seo_score',
        'content_quality_score',
    ];

    public function audit()
    {
        return $this->belongsTo(Audit::class);
    }

    public function findings()
    {
        return $this->hasMany(WebsiteAuditFinding::class);
    }
}
