<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotFlow extends Model
{
     protected $fillable = [
        'company_id',
        'flow_name',
        'flow_type',
        'triggers',
        'steps',
        'data_config',
        'priority',
        'is_active'
    ];

    protected $casts = [
        'triggers' => 'array',
        'steps' => 'array',
        'data_config' => 'array',
        'is_active' => 'boolean'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
