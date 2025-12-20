<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiRecommendation extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'audit_id',
        'category',
        'priority',
        'recommendation',
        'implementation_effort',
        'impact_level',
        'tokens_used',
        'model_used',
    ];

    public function audit()
    {
        return $this->belongsTo(Audit::class);
    }
}
