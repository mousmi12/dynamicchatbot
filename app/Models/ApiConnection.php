<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApiConnection extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'connection_name', 'api_type', 'base_url',
        'api_key', 'api_secret', 'headers', 'auth_config',
        'is_active', 'last_synced_at'
    ];

    protected $casts = [
        'headers' => 'array',
        'auth_config' => 'array',
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function endpoints()
    {
        return $this->hasMany(ApiEndpoint::class);
    }

    public function syncLogs()
    {
        return $this->hasMany(ApiSyncLog::class);
    }
}
