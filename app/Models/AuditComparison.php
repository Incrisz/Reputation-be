<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditComparison extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'audit_id_1',
        'audit_id_2',
        'score_improvement',
        'key_improvements',
        'areas_declined',
    ];

    protected $casts = [
        'key_improvements' => 'array',
        'areas_declined' => 'array',
    ];

    public function auditOne()
    {
        return $this->belongsTo(Audit::class, 'audit_id_1');
    }

    public function auditTwo()
    {
        return $this->belongsTo(Audit::class, 'audit_id_2');
    }
}
