<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Companysetting extends Model
{
    protected $table = 'company_settings';
    protected $fillable = [
        'company_id',

        // Mail
        'mail_host',
        'mail_port',
        'mail_username',
        'mail_password',
        'mail_encryption',
        'mail_from_address',
        'mail_from_name',

        // Telegram
        'telegram_bot_token',

        // AI Keys
        'groq_api_key',
        'openai_api_key',
        'anthropic_api_key',

        'api_integrations',
    ];

    /**
     * Encrypt sensitive fields before saving.
     * Decrypt when reading.
     */
    protected $hidden = [
        'mail_password',
        'telegram_bot_token',
        'groq_api_key',
        'openai_api_key',
        'anthropic_api_key',
    ];

    // ─── Encrypt on SET ────────────────────────────────────────────────

    public function setMailPasswordAttribute($value): void
    {
        $this->attributes['mail_password'] = $value ? encrypt($value) : null;
    }

    public function setTelegramBotTokenAttribute($value): void
    {
        $this->attributes['telegram_bot_token'] = $value ? encrypt($value) : null;
    }

    public function setGroqApiKeyAttribute($value): void
    {
        $this->attributes['groq_api_key'] = $value ? encrypt($value) : null;
    }

    public function setOpenaiApiKeyAttribute($value): void
    {
        $this->attributes['openai_api_key'] = $value ? encrypt($value) : null;
    }

    public function setAnthropicApiKeyAttribute($value): void
    {
        $this->attributes['anthropic_api_key'] = $value ? encrypt($value) : null;
    }

    // ─── Decrypt on GET ────────────────────────────────────────────────

    public function getMailPasswordAttribute($value): ?string
    {
        return $value ? decrypt($value) : null;
    }

    public function getTelegramBotTokenAttribute($value): ?string
    {
        return $value ? decrypt($value) : null;
    }

    public function getGroqApiKeyAttribute($value): ?string
    {
        return $value ? decrypt($value) : null;
    }

    public function getOpenaiApiKeyAttribute($value): ?string
    {
        return $value ? decrypt($value) : null;
    }

    public function getAnthropicApiKeyAttribute($value): ?string
    {
        return $value ? decrypt($value) : null;
    }

    // ─── Relationship ──────────────────────────────────────────────────

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

// api_integrations — encrypted JSON cast
protected $casts = [
    'api_integrations' => \App\Casts\EncryptedJson::class,
];
    
}
