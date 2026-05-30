<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotIntegration extends Model
{
    protected $fillable = [
        'company_id', 'integration_type', 'enable_order_tracking',
        'enable_medicine_info', 'enable_driver_info', 'features_config',
        'is_active'
    ];

    protected $casts = [
        'enable_order_tracking' => 'boolean',
        'enable_medicine_info' => 'boolean',
        'enable_driver_info' => 'boolean',
        'features_config' => 'array',
        'is_active' => 'boolean'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function isFeatureEnabled($feature)
    {
        return $this->is_active && $this->{"enable_$feature"} ?? false;
    }
}
