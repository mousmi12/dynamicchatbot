@extends('admin.layouts.admin')

@section('content')
<div class="p-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-3xl font-bold text-gray-800">{{ $company->name }} - Chatbot Configuration</h2>
                <p class="text-gray-600 text-sm mt-1">Manage chatbot messages for this company</p>
            </div>
            <a href="{{ route('admin.companies.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                <i class="fas fa-arrow-left mr-2"></i>Back to Companies
            </a>
        </div>

        @php
            $hasWelcome = $chatbotconfigs->where('config_key', 'welcome_message')->isNotEmpty();
            $hasGoodbye = $chatbotconfigs->where('config_key', 'goodbye_message')->isNotEmpty();
            $hasError = $chatbotconfigs->where('config_key', 'error_message')->isNotEmpty();
            
        @endphp

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <!-- Configuration Status -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-4">Configuration Status</h3>
                <div class="space-y-3">
                    <div class="flex items-center gap-2">
                        @if($hasWelcome)
                            <i class="fas fa-check-circle text-green-600"></i>
                            <span class="text-sm text-gray-700">Welcome Message Configured</span>
                        @else
                            <i class="fas fa-circle-xmark text-gray-400"></i>
                            <span class="text-sm text-gray-400">Welcome Message Not Configured</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        @if($hasGoodbye)
                            <i class="fas fa-check-circle text-green-600"></i>
                            <span class="text-sm text-gray-700">Goodbye Message Configured</span>
                        @else
                            <i class="fas fa-circle-xmark text-gray-400"></i>
                            <span class="text-sm text-gray-400">Goodbye Message Not Configured</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        @if($hasError)
                            <i class="fas fa-check-circle text-green-600"></i>
                            <span class="text-sm text-gray-700">Error Message Configured</span>
                        @else
                            <i class="fas fa-circle-xmark text-gray-400"></i>
                            <span class="text-sm text-gray-400">Error Message Not Configured</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Action Button -->
            <a href="{{ route('admin.chatbotconfigs.create', ['company_id' => $company->id]) }}" 
               class="inline-block bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg text-sm font-medium transition">
                @if($chatbotconfigs->count() > 0)
                    <i class="fas fa-pen mr-2"></i>Edit Configuration
                @else
                    <i class="fas fa-plus mr-2"></i>Setup Configuration
                @endif
            </a>
        </div>
    </div>
</div>
@endsection