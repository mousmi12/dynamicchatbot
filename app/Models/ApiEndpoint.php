<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiEndpoint extends Model
{
    protected $fillable = [
        'api_connection_id', 'endpoint_name', 'endpoint_path', 'method',
        'query_params', 'request_body', 'cache_key_prefix', 'cache_duration',
        'is_active'
    ];

    protected $casts = [
        'query_params' => 'array',
        'request_body' => 'array',
        'is_active' => 'boolean'
    ];

    public function apiConnection()
    {
        return $this->belongsTo(ApiConnection::class);
    }

    public function cachedData()
    {
        return $this->hasMany(CachedApiData::class);
    }
}
