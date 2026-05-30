@extends('admin.layouts.admin')

@section('content')
<div class="p-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Chatbot Configuration</h2>
                <p class="text-gray-600 text-sm mt-1">Customize your chatbot messages and behavior</p>
            </div>
            <a href="{{ route('admin.companies.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                <i class="fas fa-arrow-left mr-2"></i>Back
            </a>
        </div>

        <!-- Company Info -->
        <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4 mb-6">
            <p class="text-indigo-800">
                <strong>Company:</strong> {{ $company->name }} 
                <span class="text-indigo-600">({{ $company->slug }})</span>
            </p>
        </div>

        <!-- Form -->
        <form action="{{ route('admin.chatbotconfigs.store', $company) }}" method="POST" class="bg-white rounded-xl shadow-sm p-6">
            @csrf

            <!-- Welcome Message -->
            <div class="mb-8 pb-8 border-b">
                <div class="flex items-center mb-4">
                    <i class="fas fa-hand-peace text-indigo-600 mr-3 text-xl"></i>
                    <label class="block text-gray-700 font-semibold">Welcome Message</label>
                </div>
                <p class="text-gray-500 text-sm mb-3">Message shown when user starts chatting</p>
                <textarea name="welcome_message"
                    rows="5"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('welcome_message') border-red-500 @enderror"
                    placeholder="Hi there! 👋&#10;&#10;Welcome to our chatbot.&#10;&#10;How can I help you today?">{{ old('welcome_message', $configs['welcome_message'] ?? '') }}</textarea>
                <p class="text-gray-500 text-xs mt-2">💡 Tip: You can use emojis and line breaks (\n)</p>
                @error('welcome_message')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Goodbye Message -->
            <div class="mb-8 pb-8 border-b">
                <div class="flex items-center mb-4">
                    <i class="fas fa-wave-hand text-indigo-600 mr-3 text-xl"></i>
                    <label class="block text-gray-700 font-semibold">Goodbye Message</label>
                </div>
                <p class="text-gray-500 text-sm mb-3">Message shown when user ends the chat</p>
                <textarea name="goodbye_message"
                    rows="5"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('goodbye_message') border-red-500 @enderror"
                    placeholder="Thank you for contacting us! 😊&#10;&#10;Have a great day!">{{ old('goodbye_message', $configs['goodbye_message'] ?? '') }}</textarea>
                <p class="text-gray-500 text-xs mt-2">💡 Tip: You can use emojis and line breaks (\n)</p>
                @error('goodbye_message')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Error Message -->
            <div class="mb-8 pb-8 border-b">
                <div class="flex items-center mb-4">
                    <i class="fas fa-exclamation-circle text-red-600 mr-3 text-xl"></i>
                    <label class="block text-gray-700 font-semibold">Error Message</label>
                </div>
                <p class="text-gray-500 text-sm mb-3">Message shown when an error occurs</p>
                <textarea name="error_message"
                    rows="5"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('error_message') border-red-500 @enderror"
                    placeholder="Sorry, there was an error 😔&#10;&#10;Please contact us directly.">{{ old('error_message', $configs['error_message'] ?? '') }}</textarea>
                @error('error_message')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Demo Success Message -->
            <div class="mb-8 pb-8 border-b">
                <div class="flex items-center mb-4">
                    <i class="fas fa-check-circle text-green-600 mr-3 text-xl"></i>
                    <label class="block text-gray-700 font-semibold">Demo Request Success Message</label>
                </div>
                <p class="text-gray-500 text-sm mb-3">Message shown after successful demo request submission</p>
                <textarea name="demo_success_message"
                    rows="5"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('demo_success_message') border-red-500 @enderror"
                    placeholder="Perfect! 😊&#10;&#10;✅ Your demo request has been received!&#10;&#10;Our team will contact you shortly.">{{ old('demo_success_message', $configs['demo_success_message'] ?? '') }}</textarea>
                @error('demo_success_message')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Enquiry Success Message -->
            <div class="mb-8 pb-8 border-b">
                <div class="flex items-center mb-4">
                    <i class="fas fa-check-circle text-green-600 mr-3 text-xl"></i>
                    <label class="block text-gray-700 font-semibold">Enquiry Success Message</label>
                </div>
                <p class="text-gray-500 text-sm mb-3">Message shown after successful enquiry submission</p>
                <textarea name="enquiry_success_message"
                    rows="5"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('enquiry_success_message') border-red-500 @enderror"
                    placeholder="Perfect! ✅&#10;&#10;Your requirement has been submitted.&#10;&#10;Our team will review it and get back to you soon.">{{ old('enquiry_success_message', $configs['enquiry_success_message'] ?? '') }}</textarea>
                @error('enquiry_success_message')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Tracking Prompt (Optional) -->
            <div class="mb-8">
                <div class="flex items-center mb-4">
                    <i class="fas fa-box text-blue-600 mr-3 text-xl"></i>
                    <label class="block text-gray-700 font-semibold">Tracking Prompt (Optional)</label>
                </div>
                <p class="text-gray-500 text-sm mb-3">Message for order tracking feature (if applicable)</p>
                <textarea name="tracking_prompt"
                    rows="5"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('tracking_prompt') border-red-500 @enderror"
                    placeholder="Sure! I can help you track your order. 📦&#10;&#10;Please provide your tracking number or order ID.">{{ old('tracking_prompt', $configs['tracking_prompt'] ?? '') }}</textarea>
                @error('tracking_prompt')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.companies.index') }}" class="bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transition">
                    Cancel
                </a>
                <button type="submit" class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition">
                    <i class="fas fa-save mr-2"></i>Save Configuration
                </button>
            </div>
        </form>

        <!-- Preview Card -->
        <div class="mt-8 bg-gradient-to-r from-indigo-50 to-blue-50 rounded-xl p-6 border border-indigo-200">
            <h3 class="text-lg font-bold text-gray-800 mb-4">
                <i class="fas fa-eye mr-2"></i>Preview
            </h3>
            <div class="bg-white rounded-lg p-4 border border-gray-200 max-h-96 overflow-y-auto">
                <div class="space-y-3">
                    <div class="flex gap-2">
                        <div class="bg-indigo-100 text-indigo-900 rounded-lg px-4 py-2 max-w-xs">
                            <p class="text-sm whitespace-pre-wrap">{{ $configs['welcome_message'] ?? 'Welcome message will appear here...' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection