<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialMediaProfile extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'audit_id',
        'platform',
        'url',
        'presence_detected',
        'linked_from_website',
        'profile_quality_estimate',
        'followers_estimate',
        'verified',
    ];

    protected $casts = [
        'presence_detected' => 'boolean',
        'linked_from_website' => 'boolean',
        'verified' => 'boolean',
    ];

    public function audit()
    {
        return $this->belongsTo(Audit::class);
    }
}
