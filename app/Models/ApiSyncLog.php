<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiSyncLog extends Model
{
   protected $fillable = [
        'company_id', 'api_endpoint_id', 'sync_type', 'status',
        'records_synced', 'error_message', 'request_params',
        'response_summary', 'started_at', 'completed_at'
    ];

    protected $casts = [
        'request_params' => 'array',
        'response_summary' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function apiEndpoint()
    {
        return $this->belongsTo(ApiEndpoint::class);
    }
}
