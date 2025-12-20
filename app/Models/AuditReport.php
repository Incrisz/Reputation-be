<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'audit_id',
        'report_type',
        'file_path',
        'file_size',
        'share_token',
        'share_expires_at',
        'download_count',
        'shared_with_count',
        'expires_at',
    ];

    protected $casts = [
        'share_expires_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function audit()
    {
        return $this->belongsTo(Audit::class);
    }
}
