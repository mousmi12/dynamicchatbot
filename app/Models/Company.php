<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo',
        'phone_numbers',
        'email_addresses',
        'website',
        'primary_color',
        'secondary_color',
        'bot_name',
        'bot_avatar',
        'ai_provider',
        'ai_model',
        'ai_temperature',
        'ai_max_tokens',
        'notification_email',
        'is_active'
    ];

    protected $casts = [
        'phone_numbers' => 'array',
        'email_addresses' => 'array',
        'ai_temperature' => 'float',
        'is_active' => 'boolean'
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function chatbotConfigs()
    {
        return $this->hasMany(ChatbotConfig::class);
    }

    public function buttonTemplates()
    {
        return $this->hasMany(ButtonTemplate::class);
    }

    public function getConfig($key, $default = null)
    {
        $config = $this->chatbotConfigs()
            ->where('config_key', $key)
            ->first();

        return $config ? $config->config_value : $default;
    }

    public function getActiveProducts()
    {
        return $this->products()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();
    }

    public function getActiveServices()
    {
        return $this->services()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();
    }
    public function chatbotFlows()
    {
        return $this->hasMany(ChatbotFlow::class);
    }
    public function settings()
    {
        return $this->hasOne(CompanySetting::class);
    }
    public function apiIntegrations()
    {
        return $this->hasMany(CompanyApiIntegration::class);
    }
}
