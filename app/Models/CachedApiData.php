<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CachedApiData extends Model
{
     protected $fillable = [
        'company_id', 'api_endpoint_id', 'data_type', 'cache_key',
        'data', 'cached_at', 'expires_at'
    ];

    protected $casts = [
        'data' => 'array',
        'cached_at' => 'datetime',
        'expires_at' => 'datetime'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function apiEndpoint()
    {
        return $this->belongsTo(ApiEndpoint::class);
    }

    public function isExpired()
    {
        return $this->expires_at < now();
    }

    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now());
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('data_type', $type);
    }
}
