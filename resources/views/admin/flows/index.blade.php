@extends('admin.layouts.admin')

@section('content')
<div class="p-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>ggffj
                <h2 class="text-3xl font-bold text-gray-800">Chatbot Flows</h2>
                <p class="text-gray-600 text-sm mt-1">{{ $company->name }} - Manage conversation flows</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.companies.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                    <i class="fas fa-arrow-left mr-2"></i>Back
                </a>
                <a href="{{ route('admin.flows.create', $company) }}" class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 flex items-center">
                    <i class="fas fa-plus mr-2"></i>Add Flow
                </a>
            </div>
        </div>

        <!-- Success Message -->
        @if (session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6 flex items-start">
            <i class="fas fa-check-circle mr-3 text-green-600 mt-0.5"></i>
            <div>
                <p class="font-semibold">Success</p>
                <p class="text-sm">{{ session('success') }}</p>
            </div>
        </div>
        @endif

        <!-- Flows Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($flows as $flow)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition">
                <!-- Header -->
                <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4 text-white">
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="font-bold text-lg">{{ $flow->flow_name }}</h3>
                        <span class="px-2 py-1 bg-white/20 rounded text-xs font-semibold">
                            {{ str_replace('_', ' ', ucfirst($flow->flow_type)) }}
                        </span>
                    </div>
                    <p class="text-indigo-100 text-sm">Priority: {{ $flow->priority }}</p>
                </div>

                <!-- Content -->
                <div class="px-6 py-4 space-y-4">
                    <!-- Triggers -->

                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2">Triggers</p>
                        <div class="flex flex-wrap gap-2">
                            @php
                            // Handle both array and JSON string
                            $triggers = is_array($flow->triggers)
                            ? $flow->triggers
                            : (json_decode($flow->triggers, true) ?? []);
                            @endphp
                            @forelse($triggers as $trigger)
                            <span class="inline-block bg-blue-50 text-blue-700 px-2 py-1 rounded text-xs">
                                "{{ $trigger }}"
                            </span>
                            @empty
                            <p class="text-gray-500 text-xs">No triggers</p>
                            @endforelse
                        </div>
                    </div>

                    <!-- Steps Count -->
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2">Steps</p>
                        @php
                        // Handle both array and JSON string
                        $steps = is_array($flow->steps)
                        ? $flow->steps
                        : (json_decode($flow->steps, true) ?? []);
                        @endphp
                        <p class="text-2xl font-bold text-indigo-600">{{ count($steps) }}</p>
                        <p class="text-xs text-gray-500">steps configured</p>
                    </div>

                    <!-- Status -->
                    <div class="pt-2 border-t">
                        @if($flow->is_active)
                        <span class="inline-flex items-center gap-1 bg-green-50 text-green-700 px-3 py-1 rounded-full text-xs font-medium">
                            <i class="fas fa-circle-check"></i>Active
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1 bg-gray-50 text-gray-700 px-3 py-1 rounded-full text-xs font-medium">
                            <i class="fas fa-circle-xmark"></i>Inactive
                        </span>
                        @endif
                    </div>
                </div>

                <!-- Actions -->
                <div class="px-6 py-4 bg-gray-50 border-t flex gap-2">
                    <a href="{{ route('admin.flows.edit', [$company, $flow]) }}"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded text-center text-sm font-medium transition">
                        <i class="fas fa-pen mr-1"></i>Edit
                    </a>
                    <form action="{{ route('admin.flows.destroy', [$company, $flow]) }}" method="POST" class="flex-1"
                        onsubmit="return confirm('Delete this flow?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded text-sm font-medium transition">
                            <i class="fas fa-trash mr-1"></i>Delete
                        </button>
                    </form>
                </div>
            </div>
            @empty
            <div class="col-span-full bg-white rounded-xl shadow-sm p-12 text-center">
                <i class="fas fa-inbox text-gray-300 text-5xl mb-4"></i>
                <p class="text-gray-500 font-semibold text-lg">No flows created yet</p>
                <p class="text-gray-400 mb-6">Create your first chatbot flow to get started</p>
                <a href="{{ route('admin.flows.create', $company) }}" class="inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition">
                    <i class="fas fa-plus mr-2"></i>Create First Flow
                </a>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection