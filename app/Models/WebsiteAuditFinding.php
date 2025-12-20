<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebsiteAuditFinding extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'website_audit_id',
        'category',
        'type',
        'finding',
        'description',
        'severity',
    ];

    public function websiteAudit()
    {
        return $this->belongsTo(WebsiteAudit::class);
    }
}
