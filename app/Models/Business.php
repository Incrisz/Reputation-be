<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'website_url',
        'business_name',
        'industry',
        'country',
        'city',
        'description',
        'keywords',
        'logo_url',
        'status',
        'last_audited_at',
    ];

    protected $casts = [
        'keywords' => 'array',
        'last_audited_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function audits()
    {
        return $this->hasMany(Audit::class);
    }
}
