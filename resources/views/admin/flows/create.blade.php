@extends('admin.layouts.admin')

@section('content')
<div class="p-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">
                    {{ isset($flow) ? 'Edit Flow' : 'Create New Flow' }}
                </h2>
                <p class="text-gray-600 text-sm mt-1">{{ $company->name }}</p>
            </div>
            <a href="{{ route('admin.flows.index', $company) }}" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                <i class="fas fa-arrow-left mr-2"></i>Back
            </a>
        </div>

        <!-- Error Message -->
        @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        </div>
        @endif

        <!-- Success Message -->
        @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
        @endif

        <!-- Form -->
        <form action="{{ isset($flow) ? route('admin.flows.update', [$company, $flow]) : route('admin.flows.store', $company) }}"
            method="POST" class="bg-white rounded-xl shadow-sm p-6">
            @csrf
            @if(isset($flow))
                @method('PUT')
            @endif

            <!-- Flow Name -->
            <div class="mb-6">
                <label class="block text-gray-700 font-semibold mb-2">
                    Flow Name <span class="text-red-500">*</span>
                </label>
                <input type="text" name="flow_name"
                    value="{{ old('flow_name', $flow->flow_name ?? '') }}"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg @error('flow_name') border-red-500 @enderror"
                    placeholder="e.g., demo_request, product_inquiry"
                    required>
                @error('flow_name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Flow Type -->
            <div class="mb-6">
                <label class="block text-gray-700 font-semibold mb-2">
                    Flow Type <span class="text-red-500">*</span>
                </label>
                <select name="flow_type" class="w-full px-4 py-3 border border-gray-300 rounded-lg @error('flow_type') border-red-500 @enderror" required>
                    <option value="">-- Select Flow Type --</option>
                    <option value="form_collection" {{ old('flow_type', $flow->flow_type ?? '') === 'form_collection' ? 'selected' : '' }}>
                        Form Collection (Collect User Data)
                    </option>
                    <option value="data_query" {{ old('flow_type', $flow->flow_type ?? '') === 'data_query' ? 'selected' : '' }}>
                        Data Query (Search Database)
                    </option>
                    <option value="quick_reply" {{ old('flow_type', $flow->flow_type ?? '') === 'quick_reply' ? 'selected' : '' }}>
                        Quick Reply (Simple Response)
                    </option>
                     <option value="api_auth" {{ old('api_auth', $flow->flow_type ?? '') === 'api_auth' ? 'selected' : '' }}>
                        Api Auth 
                    </option>
                </select>
                @error('flow_type')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Priority -->
            <div class="mb-6">
                <label class="block text-gray-700 font-semibold mb-2">Priority (Higher = Matched First)</label>
                <input type="number" name="priority"
                    value="{{ old('priority', $flow->priority ?? 0) }}"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg @error('priority') border-red-500 @enderror"
                    placeholder="0" min="0">
                @error('priority')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Triggers -->
            <div class="mb-6 pb-6 border-b">
                <label class="block text-gray-700 font-semibold mb-2">
                    Triggers <span class="text-gray-500 text-sm font-normal">(Keywords that activate this flow)</span>
                </label>
                <div id="triggersContainer" class="space-y-2 mb-3">
                    @php
                        $triggersData = old('triggers');
                        if (!$triggersData && isset($flow)) {
                            $t = $flow->triggers;
                            $triggersData = is_array($t) ? $t : (json_decode($t, true) ?? []);
                        }
                        if (empty($triggersData)) $triggersData = [''];
                    @endphp
                    @foreach($triggersData as $trigger)
                    <div class="flex gap-2 trigger-row">
                        <input type="text" name="triggers[]"
                            value="{{ $trigger }}"
                            class="flex-1 px-4 py-3 border border-gray-300 rounded-lg"
                            placeholder="e.g., demo, request demo, show demo">
                        <button type="button" onclick="removeTrigger(this)" class="bg-red-500 text-white px-4 rounded-lg hover:bg-red-600">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    @endforeach
                </div>
                <button type="button" onclick="addTrigger()" class="bg-indigo-100 text-indigo-600 px-4 py-2 rounded-lg hover:bg-indigo-200">
                    <i class="fas fa-plus mr-2"></i>Add Trigger
                </button>
            </div>

            <!-- Steps -->
            <div class="mb-6 pb-6 border-b">
                <label class="block text-gray-700 font-semibold mb-2">
                    Steps <span class="text-gray-500 text-sm font-normal">(Questions/Messages in sequence)</span>
                </label>
                <div id="stepsContainer" class="space-y-4 mb-4">
                    @php
                        $stepsData = old('steps');
                        if (!$stepsData && isset($flow)) {
                            $s = $flow->steps;
                            $stepsData = is_array($s) ? $s : (json_decode($s, true) ?? []);
                        }
                        if (empty($stepsData)) $stepsData = [['message' => '', 'cache_key' => '', 'field_name' => '']];
                    @endphp
                    @foreach($stepsData as $index => $step)
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 step-row">
                        <div class="flex justify-between items-center mb-3">
                            <h4 class="font-semibold text-gray-700">Step {{ $index + 1 }}</h4>
                            <button type="button" onclick="removeStep(this)" class="bg-red-500 text-white px-3 py-1 rounded text-sm hover:bg-red-600">
                                <i class="fas fa-trash mr-1"></i>Remove
                            </button>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <label class="text-sm font-semibold text-gray-700 mb-1 block">Message *</label>
                                <textarea name="steps[{{ $index }}][message]" rows="3"
                                    class="w-full px-3 py-2 border border-gray-300 rounded"
                                    placeholder="What do you want to ask?">{{ $step['message'] ?? '' }}</textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-sm font-semibold text-gray-700 mb-1 block">Cache Key *</label>
                                    <input type="text" name="steps[{{ $index }}][cache_key]"
                                        value="{{ $step['cache_key'] ?? '' }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded"
                                        placeholder="e.g., demo_name">
                                </div>
                                <div>
                                    <label class="text-sm font-semibold text-gray-700 mb-1 block">Field Name *</label>
                                    <input type="text" name="steps[{{ $index }}][field_name]"
                                        value="{{ $step['field_name'] ?? '' }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded"
                                        placeholder="e.g., customer_name">
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                <button type="button" onclick="addStep()" class="bg-indigo-100 text-indigo-600 px-4 py-2 rounded-lg hover:bg-indigo-200">
                    <i class="fas fa-plus mr-2"></i>Add Step
                </button>
            </div>

            <!-- Data Config (JSON) -->
            <div class="mb-6">
                <label class="block text-gray-700 font-semibold mb-2">
                    Data Configuration <span class="text-gray-500 text-sm font-normal">(Advanced - JSON)</span>
                </label>
                @php
                    $dataConfigValue = old('data_config');
                    if (!$dataConfigValue && isset($flow)) {
                        $dc = $flow->data_config;
                        $dataConfigValue = is_array($dc) ? json_encode($dc, JSON_PRETTY_PRINT) : ($dc ?? '{}');
                    }
                    if (empty($dataConfigValue)) $dataConfigValue = '{}';
                @endphp
                <textarea name="data_config" rows="8"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg font-mono text-sm @error('data_config') border-red-500 @enderror"
                    placeholder='{&#10;  "save_to_table": "demo_requests",&#10;  "send_email": true,&#10;  "success_message": "Your request has been received!"&#10;}'>{{ $dataConfigValue }}</textarea>
                <p class="text-gray-500 text-xs mt-2">Define where to save data, email settings, success message, etc.</p>
                @error('data_config')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Active Status -->
            <div class="mb-6">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" name="is_active" value="1"
                        {{ old('is_active', $flow->is_active ?? true) ? 'checked' : '' }}
                        class="w-5 h-5 text-indigo-600 rounded">
                    <span class="ml-3 text-gray-700 font-semibold">Active</span>
                </label>
                <p class="text-gray-500 text-xs mt-1">Only active flows will be triggered in the chatbot</p>
            </div>

            <!-- Submit -->
            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.flows.index', $company) }}" class="bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600">
                    Cancel
                </a>
                <button type="submit" class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700">
                    <i class="fas fa-save mr-2"></i>{{ isset($flow) ? 'Update' : 'Create' }} Flow
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function addTrigger() {
        const container = document.getElementById('triggersContainer');
        const row = document.createElement('div');
        row.className = 'flex gap-2 trigger-row';
        row.innerHTML = `
            <input type="text" name="triggers[]"
                class="flex-1 px-4 py-3 border border-gray-300 rounded-lg"
                placeholder="e.g., demo, request demo">
            <button type="button" onclick="removeTrigger(this)" class="bg-red-500 text-white px-4 rounded-lg hover:bg-red-600">
                <i class="fas fa-trash"></i>
            </button>
        `;
        container.appendChild(row);
    }

    function removeTrigger(btn) {
        if (document.querySelectorAll('.trigger-row').length > 1) {
            btn.closest('.trigger-row').remove();
        }
    }

    function addStep() {
        const container = document.getElementById('stepsContainer');
        const stepCount = container.querySelectorAll('.step-row').length;
        const row = document.createElement('div');
        row.className = 'bg-gray-50 p-4 rounded-lg border border-gray-200 step-row';
        row.innerHTML = `
            <div class="flex justify-between items-center mb-3">
                <h4 class="font-semibold text-gray-700">Step ${stepCount + 1}</h4>
                <button type="button" onclick="removeStep(this)" class="bg-red-500 text-white px-3 py-1 rounded text-sm hover:bg-red-600">
                    <i class="fas fa-trash mr-1"></i>Remove
                </button>
            </div>
            <div class="space-y-3">
                <div>
                    <label class="text-sm font-semibold text-gray-700 mb-1 block">Message *</label>
                    <textarea name="steps[${stepCount}][message]" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded"
                        placeholder="What do you want to ask?"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-sm font-semibold text-gray-700 mb-1 block">Cache Key *</label>
                        <input type="text" name="steps[${stepCount}][cache_key]"
                            class="w-full px-3 py-2 border border-gray-300 rounded"
                            placeholder="e.g., demo_name">
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gray-700 mb-1 block">Field Name *</label>
                        <input type="text" name="steps[${stepCount}][field_name]"
                            class="w-full px-3 py-2 border border-gray-300 rounded"
                            placeholder="e.g., customer_name">
                    </div>
                </div>
            </div>
        `;
        container.appendChild(row);
    }

    function removeStep(btn) {
        if (document.querySelectorAll('.step-row').length > 1) {
            btn.closest('.step-row').remove();
        }
    }
</script>
@endsection