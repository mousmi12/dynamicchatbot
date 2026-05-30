<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Companysetting;
use Illuminate\Http\Request;

class CompanySettingsController extends Controller
{
   /**
     * Show the settings form for a company.
     */
    public function edit(Company $company)
    {
       // dd("Settings");
        $settings = $company->settings ?? new CompanySetting(['company_id' => $company->id]);
        return view('admin.companies.settings', compact('company', 'settings'));
    }

    /**
     * Save / update settings.
     * Sensitive fields are only updated when a non-empty value is submitted.
     */
    public function update(Request $request, Company $company)
    {
        $request->validate([
            // Mail
            'mail_host'         => 'nullable|string|max:255',
            'mail_port'         => 'nullable|integer',
            'mail_username'     => 'nullable|string|max:255',
            'mail_password'     => 'nullable|string|max:255',
            'mail_encryption'   => 'nullable|in:tls,ssl,none',
            'mail_from_address' => 'nullable|email|max:255',
            'mail_from_name'    => 'nullable|string|max:255',

            // Telegram
            'telegram_bot_token' => 'nullable|string|max:500',

            // AI Keys
            'groq_api_key'      => 'nullable|string|max:500',
            'openai_api_key'    => 'nullable|string|max:500',
            'anthropic_api_key' => 'nullable|string|max:500',
        ]);

        $settings = $company->settings ?? new Companysetting(['company_id' => $company->id]);

        // ─── Non-sensitive fields: always update ───────────────────────
        $settings->fill([
            'mail_host'         => $request->mail_host,
            'mail_port'         => $request->mail_port,
            'mail_username'     => $request->mail_username,
            'mail_encryption'   => $request->mail_encryption,
            'mail_from_address' => $request->mail_from_address,
            'mail_from_name'    => $request->mail_from_name,
        ]);

        // ─── Sensitive fields: only update if user typed a new value ───
        // (empty = keep existing encrypted value)
        if ($request->filled('mail_password')) {
            $settings->mail_password = $request->mail_password;
        }
        if ($request->filled('telegram_bot_token')) {
            $settings->telegram_bot_token = $request->telegram_bot_token;
        }
        if ($request->filled('groq_api_key')) {
            $settings->groq_api_key = $request->groq_api_key;
        }
        if ($request->filled('openai_api_key')) {
            $settings->openai_api_key = $request->openai_api_key;
        }
        if ($request->filled('anthropic_api_key')) {
            $settings->anthropic_api_key = $request->anthropic_api_key;
        }

        $settings->company_id = $company->id;
        $settings->save();

        return redirect()
            ->route('admin.companies.index', $company)
            ->with('success', 'Company Settings saved successfully!');
    }
}
