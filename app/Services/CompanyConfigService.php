<?php

namespace App\Services;

use App\Models\Company;

class CompanyConfigService
{
    /**
     * Override Laravel's runtime config with this company's settings.
     * Call this from middleware or at the start of any company-scoped request.
     */
    public static function apply(Company $company): void
    {
        $s = $company->settings;

        if (!$s) return;

        // ─── Mail ──────────────────────────────────────────────────────
        config([
            'mail.mailers.smtp.host'       => $s->mail_host       ?? config('mail.mailers.smtp.host'),
            'mail.mailers.smtp.port'       => $s->mail_port       ?? config('mail.mailers.smtp.port'),
            'mail.mailers.smtp.username'   => $s->mail_username   ?? config('mail.mailers.smtp.username'),
            'mail.mailers.smtp.password'   => $s->mail_password   ?? config('mail.mailers.smtp.password'),
            'mail.mailers.smtp.encryption' => $s->mail_encryption ?? config('mail.mailers.smtp.encryption'),
            'mail.from.address'            => $s->mail_from_address ?? config('mail.from.address'),
            'mail.from.name'               => $s->mail_from_name    ?? config('mail.from.name'),
        ]);

        // ─── Telegram ──────────────────────────────────────────────────
        if ($s->telegram_bot_token) {
            config(['services.telegram.token' => $s->telegram_bot_token]);
        }

        // ─── AI API Key (based on company's chosen provider) ───────────
        $keyMap = [
            'groq'      => $s->groq_api_key,
            'openai'    => $s->openai_api_key,
            'anthropic' => $s->anthropic_api_key,
        ];

        $activeKey = $keyMap[$company->ai_provider] ?? null;

        if ($activeKey) {
            config(['services.' . $company->ai_provider . '.api_key' => $activeKey]);
        }
    }
}