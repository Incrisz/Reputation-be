<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoogleBusinessProfile extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'audit_id',
        'detected',
        'listing_quality_score',
        'nap_consistency',
        'review_count',
        'rating',
        'complete_profile',
        'profile_url',
    ];

    protected $casts = [
        'detected' => 'boolean',
        'complete_profile' => 'boolean',
        'rating' => 'decimal:1',
    ];

    public function audit()
    {
        return $this->belongsTo(Audit::class);
    }
}
