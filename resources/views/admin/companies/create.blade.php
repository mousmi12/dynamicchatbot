@extends('admin.layouts.admin')

@section('content')
<div class="p-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">
                    {{ isset($company) ? 'Edit Company' : 'Create New Company' }}
                </h2>
                <p class="text-gray-600 text-sm mt-1">
                    {{ isset($company) ? 'Update company information' : 'Add a new company to the system' }}
                </p>
            </div>
            <a href="{{ route('admin.companies.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                <i class="fas fa-arrow-left mr-2"></i>Back
            </a>
        </div>

        <!-- Form -->
        <form action="{{ isset($company) ? route('admin.companies.update', $company) : route('admin.companies.store') }}"
            method="POST"
            class="bg-white rounded-xl shadow-sm p-6">
            @csrf
            @if(isset($company))
            @method('PUT')
            @endif

            <!-- Company Name -->
            <div class="mb-6">
                <label class="block text-gray-700 font-semibold mb-2">
                    Company Name <span class="text-red-500">*</span>
                </label>
                <input type="text"
                    name="name"
                    value="{{ old('name', $company->name ?? '') }}"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('name') border-red-500 @enderror"
                    placeholder="e.g., Milestone Innovative Technologies"
                    required>
                @error('name')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Slug -->
            <div class="mb-6">
                <label class="block text-gray-700 font-semibold mb-2">
                    Slug <span class="text-gray-500 text-sm font-normal">(Auto-generated if empty)</span>
                </label>
                <input type="text"
                    name="slug"
                    value="{{ old('slug', $company->slug ?? '') }}"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('slug') border-red-500 @enderror"
                    placeholder="e.g., milestone-it">
                <p class="text-gray-500 text-xs mt-1">Used in chatbot URL: yoursite.com/<strong>slug</strong>/chat</p>
                @error('slug')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Description -->
            <div class="mb-6">
                <label class="block text-gray-700 font-semibold mb-2">Description</label>
                <textarea name="description"
                    rows="4"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('description') border-red-500 @enderror"
                    placeholder="Brief description about the company">{{ old('description', $company->description ?? '') }}</textarea>
                @error('description')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Phone Numbers (JSON Object) -->
            <div class="mb-6">
                <label class="block text-gray-700 font-semibold mb-2">
                    Phone Numbers <span class="text-gray-500 text-sm font-normal">(Multiple locations)</span>
                </label>
                <div id="phoneNumbersContainer" class="space-y-3">
                    @php
                    $phoneNumbers = [];

                    if (old('phone_numbers')) {
                    // From validation error - combine old inputs
                    $locations = old('phone_locations', []);
                    $numbers = old('phone_numbers', []);
                    foreach ($locations as $index => $location) {
                    if (!empty($location)) {
                    $phoneNumbers[$location] = $numbers[$index] ?? '';
                    }
                    }
                    } elseif (isset($company) && $company->phone_numbers) {
                    // 🔥 FIX: Check if already array or JSON string
                    if (is_array($company->phone_numbers)) {
                    $phoneNumbers = $company->phone_numbers;
                    } elseif (is_string($company->phone_numbers)) {
                    $phoneNumbers = json_decode($company->phone_numbers, true) ?? [];
                    }
                    }

                    // Default if empty
                    if (empty($phoneNumbers)) {
                    $phoneNumbers = ['' => ''];
                    }
                    @endphp

                    @foreach($phoneNumbers as $location => $phone)
                    <div class="flex gap-3 phone-number-row">
                        <input type="text"
                            name="phone_locations[]"
                            value="{{ $location }}"
                            class="w-1/3 px-4 py-3 border border-gray-300 rounded-lg @error('phone_locations.*') border-red-500 @enderror"
                            placeholder="e.g., India, UAE, Customer Service">
                        <input type="text"
                            name="phone_numbers[]"
                            value="{{ $phone }}"
                            class="flex-1 px-4 py-3 border border-gray-300 rounded-lg @error('phone_numbers.*') border-red-500 @enderror"
                            placeholder="e.g., +971 4 123 4567">
                        <button type="button" onclick="removePhoneRow(this)" class="bg-red-500 text-white px-4 rounded-lg hover:bg-red-600 transition">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    @endforeach
                </div>
                @error('phone_locations.*')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
                @error('phone_numbers.*')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
                <button type="button" onclick="addPhoneRow()" class="mt-3 bg-indigo-100 text-indigo-600 px-4 py-2 rounded-lg hover:bg-indigo-200 transition">
                    <i class="fas fa-plus mr-2"></i>Add Phone Number
                </button>
            </div>

            <!-- Email Addresses (JSON Array) -->
            <div class="mb-6">
                <label class="block text-gray-700 font-semibold mb-2">
                    Email Addresses <span class="text-gray-500 text-sm font-normal">(Multiple emails)</span>
                </label>
                <div id="emailAddressesContainer" class="space-y-3">
                    @php
                    $emailAddresses = [];

                    if (old('email_addresses')) {
                    // From validation error
                    $emailAddresses = array_filter(old('email_addresses', []));
                    } elseif (isset($company) && $company->email_addresses) {
                    // 🔥 FIX: Check if already array or JSON string
                    if (is_array($company->email_addresses)) {
                    $emailAddresses = $company->email_addresses;
                    } elseif (is_string($company->email_addresses)) {
                    $emailAddresses = json_decode($company->email_addresses, true) ?? [];
                    }
                    }

                    // Default if empty
                    if (empty($emailAddresses)) {
                    $emailAddresses = [''];
                    }
                    @endphp

                    @foreach($emailAddresses as $email)
                    <div class="flex gap-3 email-address-row">
                        <input type="email"
                            name="email_addresses[]"
                            value="{{ $email }}"
                            class="flex-1 px-4 py-3 border border-gray-300 rounded-lg @error('email_addresses.*') border-red-500 @enderror"
                            placeholder="e.g., info@company.com">
                        <button type="button" onclick="removeEmailRow(this)" class="bg-red-500 text-white px-4 rounded-lg hover:bg-red-600 transition">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    @endforeach
                </div>
                @error('email_addresses.*')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
                <button type="button" onclick="addEmailRow()" class="mt-3 bg-indigo-100 text-indigo-600 px-4 py-2 rounded-lg hover:bg-indigo-200 transition">
                    <i class="fas fa-plus mr-2"></i>Add Email Address
                </button>
            </div>

            <!-- Website -->
            <div class="mb-6">
                <label class="block text-gray-700 font-semibold mb-2">Website</label>
                <input type="url"
                    name="website"
                    value="{{ old('website', $company->website ?? '') }}"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('website') border-red-500 @enderror"
                    placeholder="e.g., https://company.com">
                @error('website')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Notification Email -->
            <div class="mb-6">
                <label class="block text-gray-700 font-semibold mb-2">
                    Notification Email <span class="text-red-500">*</span>
                </label>
                <input type="email"
                    name="notification_email"
                    value="{{ old('notification_email', $company->notification_email ?? '') }}"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('notification_email') border-red-500 @enderror"
                    placeholder="e.g., notifications@company.com"
                    required>
                @error('notification_email')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Bot Configuration -->
            <div class="mb-6 border-t pt-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Chatbot Configuration</h3>

                <!-- Bot Name -->
                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2">Bot Name</label>
                    <input type="text"
                        name="bot_name"
                        value="{{ old('bot_name', $company->bot_name ?? 'Support Bot') }}"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('bot_name') border-red-500 @enderror"
                        placeholder="e.g., Support Bot">
                    @error('bot_name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Primary Color -->
                <div class="grid grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Primary Color</label>
                        <div class="flex gap-3">
                            <input type="color"
                                name="primary_color"
                                value="{{ old('primary_color', $company->primary_color ?? '#667eea') }}"
                                class="w-20 h-12 border border-gray-300 rounded-lg cursor-pointer">
                            <input type="text"
                                name="primary_color_text"
                                value="{{ old('primary_color', $company->primary_color ?? '#667eea') }}"
                                class="flex-1 px-4 py-3 border border-gray-300 rounded-lg"
                                placeholder="#667eea">
                        </div>
                        @error('primary_color')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Secondary Color -->
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Secondary Color</label>
                        <div class="flex gap-3">
                            <input type="color"
                                name="secondary_color"
                                value="{{ old('secondary_color', $company->secondary_color ?? '#764ba2') }}"
                                class="w-20 h-12 border border-gray-300 rounded-lg cursor-pointer">
                            <input type="text"
                                name="secondary_color_text"
                                value="{{ old('secondary_color', $company->secondary_color ?? '#764ba2') }}"
                                class="flex-1 px-4 py-3 border border-gray-300 rounded-lg"
                                placeholder="#764ba2">
                        </div>
                        @error('secondary_color')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- AI Provider -->
                <div class="grid grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">AI Provider</label>
                        <select name="ai_provider"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('ai_provider') border-red-500 @enderror">
                            <option value="groq" {{ old('ai_provider', $company->ai_provider ?? 'groq') === 'groq' ? 'selected' : '' }}>Groq</option>
                            <option value="openai" {{ old('ai_provider', $company->ai_provider ?? 'groq') === 'openai' ? 'selected' : '' }}>OpenAI</option>
                            <option value="anthropic" {{ old('ai_provider', $company->ai_provider ?? 'groq') === 'anthropic' ? 'selected' : '' }}>Anthropic</option>
                        </select>
                        @error('ai_provider')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- AI Model -->
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">AI Model</label>
                        <input type="text"
                            name="ai_model"
                            value="{{ old('ai_model', $company->ai_model ?? 'llama-3.1-8b-instant') }}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('ai_model') border-red-500 @enderror"
                            placeholder="e.g., llama-3.1-8b-instant">
                        @error('ai_model')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Temperature & Max Tokens -->
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Temperature (0-1)</label>
                        <input type="number"
                            name="ai_temperature"
                            value="{{ old('ai_temperature', $company->ai_temperature ?? '0.7') }}"
                            step="0.1"
                            min="0"
                            max="1"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('ai_temperature') border-red-500 @enderror">
                        <p class="text-gray-500 text-xs mt-1">Higher = more creative, Lower = more focused</p>
                        @error('ai_temperature')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Max Tokens</label>
                        <input type="number"
                            name="ai_max_tokens"
                            value="{{ old('ai_max_tokens', $company->ai_max_tokens ?? '300') }}"
                            min="50"
                            max="4096"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('ai_max_tokens') border-red-500 @enderror">
                        <p class="text-gray-500 text-xs mt-1">Max response length</p>
                        @error('ai_max_tokens')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Active Status -->
            <div class="mb-6">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox"
                        name="is_active"
                        value="1"
                        {{ old('is_active', $company->is_active ?? true) ? 'checked' : '' }}
                        class="w-5 h-5 text-indigo-600 rounded focus:ring-2 focus:ring-indigo-500">
                    <span class="ml-3 text-gray-700 font-semibold">Active</span>
                </label>
                <p class="text-gray-500 text-xs mt-1">Only active companies can use the chatbot</p>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.companies.index') }}" class="bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transition">
                    Cancel
                </a>
                <button type="submit" class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition">
                    <i class="fas fa-save mr-2"></i>
                    {{ isset($company) ? 'Update Company' : 'Create Company' }}
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function addPhoneRow() {
        const container = document.getElementById('phoneNumbersContainer');
        const row = document.createElement('div');
        row.className = 'flex gap-3 phone-number-row';
        row.innerHTML = `
            <input type="text" 
                   name="phone_locations[]" 
                   class="w-1/3 px-4 py-3 border border-gray-300 rounded-lg"
                   placeholder="e.g., India, UAE, Customer Service">
            <input type="text" 
                   name="phone_numbers[]" 
                   class="flex-1 px-4 py-3 border border-gray-300 rounded-lg"
                   placeholder="e.g., +971 4 123 4567">
            <button type="button" onclick="removePhoneRow(this)" class="bg-red-500 text-white px-4 rounded-lg hover:bg-red-600 transition">
                <i class="fas fa-trash"></i>
            </button>
        `;
        container.appendChild(row);
    }

    function removePhoneRow(btn) {
        if (document.querySelectorAll('.phone-number-row').length > 1) {
            btn.closest('.phone-number-row').remove();
        } else {
            alert('At least one phone number is required');
        }
    }

    function addEmailRow() {
        const container = document.getElementById('emailAddressesContainer');
        const row = document.createElement('div');
        row.className = 'flex gap-3 email-address-row';
        row.innerHTML = `
            <input type="email" 
                   name="email_addresses[]" 
                   class="flex-1 px-4 py-3 border border-gray-300 rounded-lg"
                   placeholder="e.g., info@company.com">
            <button type="button" onclick="removeEmailRow(this)" class="bg-red-500 text-white px-4 rounded-lg hover:bg-red-600 transition">
                <i class="fas fa-trash"></i>
            </button>
        `;
        container.appendChild(row);
    }

    function removeEmailRow(btn) {
        if (document.querySelectorAll('.email-address-row').length > 1) {
            btn.closest('.email-address-row').remove();
        } else {
            alert('At least one email is required');
        }
    }
</script>
@endsection