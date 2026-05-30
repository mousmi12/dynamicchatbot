@extends('admin.layouts.admin')

@section('content')
<div class="p-8">
    <div class="max-w-3xl mx-auto">

        {{-- Header --}}
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">
                    ⚙️ Settings — {{ $company->name }}
                </h2>
                <p class="text-gray-500 text-sm mt-1">Mail, Telegram, and AI API credentials</p>
            </div>
            <a href="{{ route('admin.companies.index') }}"
               class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                <i class="fas fa-arrow-left mr-2"></i>Back
            </a>
        </div>

        {{-- Alerts --}}
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                ✅ {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('admin.companies.settings.update', $company) }}"
              method="POST"
              class="space-y-8">
            @csrf
            @method('PUT')

            {{-- ════════════════════════════════════════ --}}
            {{-- MAIL SETTINGS                           --}}
            {{-- ════════════════════════════════════════ --}}
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-5 flex items-center gap-2">
                    <span class="bg-blue-100 text-blue-600 p-2 rounded-lg"><i class="fas fa-envelope"></i></span>
                    Mail / SMTP Settings
                </h3>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    {{-- Mail Host --}}
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1 text-sm">SMTP Host</label>
                        <input type="text" name="mail_host"
                               value="{{ old('mail_host', $settings->mail_host ?? '') }}"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg @error('mail_host') border-red-500 @enderror"
                               placeholder="smtp.hostinger.com">
                        @error('mail_host') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Mail Port --}}
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1 text-sm">Port</label>
                        <input type="number" name="mail_port"
                               value="{{ old('mail_port', $settings->mail_port ?? 587) }}"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg @error('mail_port') border-red-500 @enderror"
                               placeholder="587">
                        @error('mail_port') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    {{-- Mail Username --}}
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1 text-sm">Username / Email</label>
                        <input type="text" name="mail_username"
                               value="{{ old('mail_username', $settings->mail_username ?? '') }}"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg @error('mail_username') border-red-500 @enderror"
                               placeholder="info@company.com">
                        @error('mail_username') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Mail Password --}}
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1 text-sm">
                            Password
                            @if($settings->mail_password)
                                <span class="text-green-600 font-normal text-xs ml-2">✅ Saved — leave blank to keep</span>
                            @endif
                        </label>
                        <input type="password" name="mail_password"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg"
                               placeholder="{{ $settings->mail_password ? '••••••••' : 'Enter password' }}">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    {{-- Encryption --}}
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1 text-sm">Encryption</label>
                        <select name="mail_encryption"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg @error('mail_encryption') border-red-500 @enderror">
                            @foreach(['tls' => 'TLS', 'ssl' => 'SSL', 'none' => 'None'] as $val => $label)
                                <option value="{{ $val }}"
                                    {{ old('mail_encryption', $settings->mail_encryption ?? 'tls') === $val ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- From Address --}}
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1 text-sm">From Address</label>
                        <input type="email" name="mail_from_address"
                               value="{{ old('mail_from_address', $settings->mail_from_address ?? '') }}"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg @error('mail_from_address') border-red-500 @enderror"
                               placeholder="info@company.com">
                        @error('mail_from_address') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- From Name --}}
                <div>
                    <label class="block text-gray-700 font-semibold mb-1 text-sm">From Name</label>
                    <input type="text" name="mail_from_name"
                           value="{{ old('mail_from_name', $settings->mail_from_name ?? '') }}"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg @error('mail_from_name') border-red-500 @enderror"
                           placeholder="Company Chatbot">
                    @error('mail_from_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- ════════════════════════════════════════ --}}
            {{-- TELEGRAM                                --}}
            {{-- ════════════════════════════════════════ --}}
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-5 flex items-center gap-2">
                    <span class="bg-sky-100 text-sky-600 p-2 rounded-lg"><i class="fab fa-telegram"></i></span>
                    Telegram Bot
                </h3>

                <div>
                    <label class="block text-gray-700 font-semibold mb-1 text-sm">
                        Bot Token
                        @if($settings->telegram_bot_token)
                            <span class="text-green-600 font-normal text-xs ml-2">✅ Saved — leave blank to keep</span>
                        @endif
                    </label>
                    <input type="password" name="telegram_bot_token"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg font-mono"
                           placeholder="{{ $settings->telegram_bot_token ? '••••••••••••••••••••••••••' : '1234567890:ABCDEFxxxxxxxxxxxxxxxxxxxxxxxx' }}">
                    <p class="text-gray-400 text-xs mt-1">Get from @BotFather on Telegram</p>
                </div>
            </div>

            {{-- ════════════════════════════════════════ --}}
            {{-- AI API KEYS                             --}}
            {{-- ════════════════════════════════════════ --}}
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-1 flex items-center gap-2">
                    <span class="bg-purple-100 text-purple-600 p-2 rounded-lg"><i class="fas fa-robot"></i></span>
                    AI API Keys
                </h3>
                <p class="text-gray-400 text-xs mb-5 ml-12">
                    Company's AI provider is set to
                    <strong class="text-indigo-600">{{ strtoupper($company->ai_provider) }}</strong>.
                    Fill the matching key below.
                </p>

                {{-- Groq --}}
                <div class="mb-4 p-4 rounded-lg border {{ $company->ai_provider === 'groq' ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200' }}">
                    <label class="block text-gray-700 font-semibold mb-1 text-sm flex items-center gap-2">
                        Groq API Key
                        @if($company->ai_provider === 'groq')
                            <span class="bg-indigo-100 text-indigo-600 text-xs px-2 py-0.5 rounded-full">Active</span>
                        @endif
                        @if($settings->groq_api_key)
                            <span class="text-green-600 font-normal text-xs">✅ Saved</span>
                        @endif
                    </label>
                    <input type="password" name="groq_api_key"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg font-mono"
                           placeholder="{{ $settings->groq_api_key ? '••••••••••••••••••••••••••' : 'gsk_...' }}">
                </div>

                {{-- OpenAI --}}
                <div class="mb-4 p-4 rounded-lg border {{ $company->ai_provider === 'openai' ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200' }}">
                    <label class="block text-gray-700 font-semibold mb-1 text-sm flex items-center gap-2">
                        OpenAI API Key
                        @if($company->ai_provider === 'openai')
                            <span class="bg-indigo-100 text-indigo-600 text-xs px-2 py-0.5 rounded-full">Active</span>
                        @endif
                        @if($settings->openai_api_key)
                            <span class="text-green-600 font-normal text-xs">✅ Saved</span>
                        @endif
                    </label>
                    <input type="password" name="openai_api_key"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg font-mono"
                           placeholder="{{ $settings->openai_api_key ? '••••••••••••••••••••••••••' : 'sk-...' }}">
                </div>

                {{-- Anthropic --}}
                <div class="p-4 rounded-lg border {{ $company->ai_provider === 'anthropic' ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200' }}">
                    <label class="block text-gray-700 font-semibold mb-1 text-sm flex items-center gap-2">
                        Anthropic API Key
                        @if($company->ai_provider === 'anthropic')
                            <span class="bg-indigo-100 text-indigo-600 text-xs px-2 py-0.5 rounded-full">Active</span>
                        @endif
                        @if($settings->anthropic_api_key)
                            <span class="text-green-600 font-normal text-xs">✅ Saved</span>
                        @endif
                    </label>
                    <input type="password" name="anthropic_api_key"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg font-mono"
                           placeholder="{{ $settings->anthropic_api_key ? '••••••••••••••••••••••••••' : 'sk-ant-...' }}">
                </div>
            </div>

            {{-- Submit --}}
            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.companies.index') }}"
                   class="bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transition">
                    Cancel
                </a>
                <button type="submit"
                        class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition">
                    <i class="fas fa-save mr-2"></i>Save Settings
                </button>
            </div>
        </form>
    </div>
</div>
@endsection