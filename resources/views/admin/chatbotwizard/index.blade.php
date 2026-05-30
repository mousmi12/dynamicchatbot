@extends('admin.layouts.admin')

@section('content')
<div class="p-8">
    <div class="max-w-7xl mx-auto">

        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-3xl font-bold text-gray-800">Chatbot Setup Wizard</h2>
                <p class="text-gray-600 text-sm mt-1">{{ $company->name }} - Complete chatbot configuration in one place</p>
            </div>
            <a href="{{ route('admin.companies.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                <i class="fas fa-arrow-left mr-2"></i>Back to Companies
            </a>
        </div>

        <!-- Progress Tabs -->
        <div class="bg-white rounded-xl shadow-sm mb-6">
            <div class="flex border-b border-gray-200">
                <button class="tab-btn flex-1 px-6 py-4 text-center font-semibold transition" data-tab="configs">
                    <i class="fas fa-cog mr-2"></i><span>Messages</span>
                    <span class="ml-2 px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-700">{{ $chatbotConfigs->count() }}</span>
                </button>
                <button class="tab-btn flex-1 px-6 py-4 text-center font-semibold transition" data-tab="flows">
                    <i class="fas fa-diagram-project mr-2"></i><span>Flows</span>
                    <span class="ml-2 px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700">{{ $flows->count() }}</span>
                </button>
                <button class="tab-btn flex-1 px-6 py-4 text-center font-semibold transition" data-tab="buttons">
                    <i class="fas fa-hand-pointer mr-2"></i><span>Buttons</span>
                    <span class="ml-2 px-2 py-1 text-xs rounded-full bg-green-100 text-green-700">{{ $buttonTemplates->count() }}</span>
                </button>
                <button class="tab-btn flex-1 px-6 py-4 text-center font-semibold transition" data-tab="products">
                    <i class="fas fa-box mr-2"></i><span>Products</span>
                    <span class="ml-2 px-2 py-1 text-xs rounded-full bg-orange-100 text-orange-700">{{ $products->count() }}</span>
                </button>
                <button class="tab-btn flex-1 px-6 py-4 text-center font-semibold transition" data-tab="integrations">
                    <i class="fas fa-plug mr-2"></i><span>Integrations</span>
                    <span class="ml-2 px-2 py-1 text-xs rounded-full bg-pink-100 text-pink-700">{{ $apiIntegrations->count() }}</span>
                </button>
                <button class="tab-btn flex-1 px-6 py-4 text-center font-semibold transition" data-tab="services">
                    <i class="fas fa-wrench mr-2"></i><span>Services</span>
                    <span class="ml-2 px-2 py-1 text-xs rounded-full bg-indigo-100 text-indigo-700">{{ $services->count() }}</span>
                </button>
            </div>
        </div>

        <!-- Success / Error Messages -->
        @if (session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6 flex items-start">
            <i class="fas fa-check-circle mr-3 text-green-600 mt-0.5"></i>
            <div><p class="font-semibold">Success</p><p class="text-sm">{{ session('success') }}</p></div>
        </div>
        @endif

        @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6">
            <p class="font-semibold mb-1">Validation Error</p>
            <ul class="text-sm list-disc ml-4">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- Tab Content -->
        <div id="tab-content">

            <!-- ══════════════════════════════════════════
                 CONFIGS TAB
            ══════════════════════════════════════════ -->
            <div class="tab-content" data-tab="configs">
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-comments text-purple-600 mr-2"></i>Chatbot Messages
                        </h3>
                        <button onclick="openConfigModal()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg transition">
                            <i class="fas fa-plus mr-2"></i>Add Message
                        </button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @forelse($chatbotConfigs as $config)
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <span class="inline-block px-2 py-1 bg-purple-100 text-purple-700 text-xs font-semibold rounded mb-2">{{ $config->category ?? 'messages' }}</span>
                                    <h4 class="font-semibold text-gray-800">{{ str_replace('_', ' ', ucwords($config->config_key)) }}</h4>
                                </div>
                                <div class="flex gap-2">
                                    @php $configData = ['id'=>$config->id,'key'=>$config->config_key,'value'=>$config->config_value,'category'=>$config->category]; @endphp
                                    <button onclick="editConfig(this)" data-template='@json($configData, JSON_HEX_APOS | JSON_HEX_QUOT)' class="text-blue-600 hover:text-blue-700"><i class="fas fa-pen"></i></button>
                                    <form action="{{ route('admin.chatbotwizard.delete-config', [$company, $config]) }}" method="POST" class="inline" onsubmit="return confirm('Delete this config?');">
                                        @csrf @method('DELETE')
                                        <input type="hidden" name="active_tab" value="configs">
                                        <button type="submit" class="text-red-600 hover:text-red-700"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 whitespace-pre-wrap">{{ Str::limit($config->config_value, 100) }}</p>
                        </div>
                        @empty
                        <div class="col-span-2 text-center py-8">
                            <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
                            <p class="text-gray-500">No messages configured yet</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════════
                 FLOWS TAB
            ══════════════════════════════════════════ -->
            <div class="tab-content" data-tab="flows">
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-diagram-project text-blue-600 mr-2"></i>Conversation Flows
                        </h3>
                        <button onclick="openFlowModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                            <i class="fas fa-plus mr-2"></i>Add Flow
                        </button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @forelse($flows as $flow)
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h4 class="font-semibold text-gray-800">{{ $flow->flow_name }}</h4>
                                    <span class="inline-block px-2 py-1 bg-blue-100 text-blue-700 text-xs font-semibold rounded mt-1">{{ str_replace('_', ' ', ucfirst($flow->flow_type)) }}</span>
                                </div>
                                <div class="flex gap-2">
                                    <a href="{{ route('admin.flows.edit', ['company' => $company->id, 'flow' => $flow->id]) }}" class="text-blue-600 hover:text-blue-700"><i class="fas fa-pen"></i></a>
                                    <form action="{{ route('admin.chatbotwizard.delete-flow', [$company, $flow]) }}" method="POST" class="inline" onsubmit="return confirm('Delete this flow?');">
                                        @csrf @method('DELETE')
                                        <input type="hidden" name="active_tab" value="flows">
                                        <button type="submit" class="text-red-600 hover:text-red-700"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                            <div class="space-y-2 text-sm">
                                <div><span class="text-gray-600">Priority:</span> <span class="font-semibold text-gray-800">{{ $flow->priority }}</span></div>
                                <div>
                                    <span class="text-gray-600">Steps:</span>
                                    <span class="font-semibold text-gray-800">
                                        @php $steps = is_array($flow->steps) ? $flow->steps : (json_decode($flow->steps, true) ?? []); @endphp
                                        {{ count($steps) }}
                                    </span>
                                </div>
                                <div>
                                    @if($flow->is_active)
                                    <span class="inline-flex items-center gap-1 bg-green-50 text-green-700 px-2 py-1 rounded-full text-xs"><i class="fas fa-circle-check"></i>Active</span>
                                    @else
                                    <span class="inline-flex items-center gap-1 bg-gray-50 text-gray-700 px-2 py-1 rounded-full text-xs"><i class="fas fa-circle-xmark"></i>Inactive</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="col-span-3 text-center py-8">
                            <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
                            <p class="text-gray-500">No flows configured yet</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════════
                 BUTTONS TAB
            ══════════════════════════════════════════ -->
            <div class="tab-content" data-tab="buttons">
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-hand-pointer text-green-600 mr-2"></i>Button Templates
                        </h3>
                        <button onclick="openButtonModal()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition">
                            <i class="fas fa-plus mr-2"></i>Add Template
                        </button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @forelse($buttonTemplates as $template)
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h4 class="font-semibold text-gray-800">{{ $template->template_name }}</h4>
                                    <span class="inline-block px-2 py-1 bg-green-100 text-green-700 text-xs font-semibold rounded mt-1">{{ $template->context }}</span>
                                </div>
                                <div class="flex gap-2">
                                    @php
                                    $buttonData = [
                                        'id'            => $template->id,
                                        'template_name' => $template->template_name,
                                        'context'       => $template->context,
                                        'buttons'       => is_array($template->buttons) ? $template->buttons : (json_decode($template->buttons, true) ?? []),
                                        'priority'      => $template->priority,
                                        'is_active'     => $template->is_active,
                                    ];
                                    @endphp
                                    <button onclick="editButtonTemplate(this)" data-template='@json($buttonData, JSON_HEX_APOS | JSON_HEX_QUOT)' class="text-blue-600 hover:text-blue-700"><i class="fas fa-pen"></i></button>
                                    <form action="{{ route('admin.chatbotwizard.delete-button', [$company, $template]) }}" method="POST" class="inline" onsubmit="return confirm('Delete this template?');">
                                        @csrf @method('DELETE')
                                        <input type="hidden" name="active_tab" value="buttons">
                                        <button type="submit" class="text-red-600 hover:text-red-700"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                            <div class="space-y-1">
                                <p class="text-xs text-gray-600">Priority: {{ $template->priority }}</p>
                                @php $btns = is_array($template->buttons) ? $template->buttons : (json_decode($template->buttons, true) ?? []); @endphp
                                <p class="text-xs text-gray-600">Buttons: {{ count($btns) }}</p>
                                @if($template->is_active)
                                <span class="inline-flex items-center gap-1 bg-green-50 text-green-700 px-2 py-1 rounded-full text-xs"><i class="fas fa-circle-check"></i>Active</span>
                                @endif
                            </div>
                        </div>
                        @empty
                        <div class="col-span-2 text-center py-8">
                            <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
                            <p class="text-gray-500">No button templates configured yet</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════════
                 PRODUCTS TAB
            ══════════════════════════════════════════ -->
            <div class="tab-content" data-tab="products">
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-box text-orange-600 mr-2"></i>Products
                        </h3>
                        <button onclick="openProductModal()" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg transition">
                            <i class="fas fa-plus mr-2"></i>Add Product
                        </button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @forelse($products as $product)
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex items-start gap-2">
                                    <span class="text-2xl">{{ $product->icon ?? '📦' }}</span>
                                    <div>
                                        <h4 class="font-semibold text-gray-800">{{ $product->name }}</h4>
                                        <code class="text-xs text-gray-500">{{ $product->slug }}</code>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    @php
                                    $productData = [
                                        'id'                => $product->id,
                                        'name'              => $product->name,
                                        'slug'              => $product->slug,
                                        'short_description' => $product->short_description,
                                        'full_description'  => $product->full_description ?? '',
                                        'keywords'          => is_array($product->keywords) ? $product->keywords : (json_decode($product->keywords, true) ?? []),
                                        'demo_available'    => $product->demo_available,
                                        'icon'              => $product->icon ?? '',
                                        'display_order'     => $product->display_order,
                                        'is_featured'       => $product->is_featured,
                                        'is_active'         => $product->is_active,
                                    ];
                                    @endphp
                                    <button onclick="editProduct(this)" data-template='@json($productData, JSON_HEX_APOS | JSON_HEX_QUOT)' class="text-blue-600 hover:text-blue-700"><i class="fas fa-pen"></i></button>
                                    <form action="{{ route('admin.chatbotwizard.delete-product', [$company, $product]) }}" method="POST" class="inline" onsubmit="return confirm('Delete this product?');">
                                        @csrf @method('DELETE')
                                        <input type="hidden" name="active_tab" value="products">
                                        <button type="submit" class="text-red-600 hover:text-red-700"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 mb-2">{{ Str::limit($product->short_description, 80) }}</p>
                            <div class="flex gap-2 flex-wrap">
                                @if($product->is_featured)
                                <span class="inline-flex items-center gap-1 bg-yellow-50 text-yellow-700 px-2 py-1 rounded-full text-xs"><i class="fas fa-star"></i>Featured</span>
                                @endif
                                @if($product->is_active)
                                <span class="inline-flex items-center gap-1 bg-green-50 text-green-700 px-2 py-1 rounded-full text-xs"><i class="fas fa-circle-check"></i>Active</span>
                                @endif
                            </div>
                        </div>
                        @empty
                        <div class="col-span-3 text-center py-8">
                            <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
                            <p class="text-gray-500">No products added yet</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════════
                 INTEGRATIONS TAB
            ══════════════════════════════════════════ -->
            <div class="tab-content" data-tab="integrations">
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-plug text-pink-600 mr-2"></i>API Integrations
                        </h3>
                        <button onclick="openIntegrationModal()" class="bg-pink-600 hover:bg-pink-700 text-white px-4 py-2 rounded-lg transition">
                            <i class="fas fa-plus mr-2"></i>Add Integration
                        </button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @forelse($apiIntegrations as $integration)
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <span class="inline-block px-2 py-1 bg-pink-100 text-pink-700 text-xs font-semibold rounded mb-2">{{ $integration->integration_key }}</span>
                                    <p class="text-sm text-gray-700 font-medium">{{ $integration->auth_base_url }}</p>
                                </div>
                                <div class="flex gap-2">
                                    {{-- Edit button: pass full integration data as JSON --}}
                                    @php
                                    $integrationData = [
                                        'id'                    => $integration->id,
                                        'integration_key'       => $integration->integration_key,
                                        'auth_base_url'         => $integration->auth_base_url,
                                        'auth_endpoint'         => $integration->auth_endpoint,
                                        'token_path'            => $integration->token_path,
                                        'auth_identifier_field' => $integration->auth_identifier_field,
                                        'token_ttl'             => $integration->token_ttl,
                                        'services'              => $integration->services ?? [],
                                    ];
                                    @endphp
                                    <button
                                        onclick="editIntegration(this)"
                                        data-template='@json($integrationData, JSON_HEX_APOS | JSON_HEX_QUOT)'
                                        class="text-blue-600 hover:text-blue-700">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <form method="POST"
                                        action="{{ route('admin.chatbotwizard.integrations.delete', [$company, $integration->integration_key]) }}"
                                        onsubmit="return confirm('Remove this integration?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                            <div class="space-y-1">
                                <p class="text-xs text-gray-500">Auth: {{ $integration->auth_endpoint }}</p>
                                <p class="text-xs text-gray-500">Token path: {{ $integration->token_path }}</p>
                                <p class="text-xs text-gray-500">TTL: {{ $integration->token_ttl }}s</p>
                                @if(!empty($integration->services))
                                <div class="flex flex-wrap gap-1 mt-2">
                                    @foreach($integration->services as $svcKey => $svcVal)
                                    <span class="inline-block px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded">{{ $svcKey }}</span>
                                    @endforeach
                                </div>
                                @endif
                                @if($integration->is_active)
                                <span class="inline-flex items-center gap-1 bg-green-50 text-green-700 px-2 py-1 rounded-full text-xs mt-1"><i class="fas fa-circle-check"></i>Active</span>
                                @endif
                            </div>
                        </div>
                        @empty
                        <div class="col-span-2 text-center py-8">
                            <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
                            <p class="text-gray-500">No integrations configured yet</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════════
                 SERVICES TAB
            ══════════════════════════════════════════ -->
            <div class="tab-content" data-tab="services">
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-wrench text-indigo-600 mr-2"></i>Services
                        </h3>
                        <button onclick="openServiceModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition">
                            <i class="fas fa-plus mr-2"></i>Add Service
                        </button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @forelse($services as $service)
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex items-start gap-2">
                                    <span class="text-2xl">{{ $service->icon ?? '🔧' }}</span>
                                    <div>
                                        <h4 class="font-semibold text-gray-800">{{ $service->name }}</h4>
                                        <code class="text-xs text-gray-500">{{ $service->slug }}</code>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    @php
                                    $serviceData = [
                                        'id'                => $service->id,
                                        'name'              => $service->name,
                                        'slug'              => $service->slug,
                                        'short_description' => $service->short_description,
                                        'full_description'  => $service->full_description ?? '',
                                        'keywords'          => is_array($service->keywords) ? $service->keywords : (json_decode($service->keywords, true) ?? []),
                                        'enquiry_available' => $service->enquiry_available,
                                        'icon'              => $service->icon ?? '',
                                        'display_order'     => $service->display_order,
                                        'is_active'         => $service->is_active,
                                    ];
                                    @endphp
                                    <button onclick="editService(this)" data-template='@json($serviceData, JSON_HEX_APOS | JSON_HEX_QUOT)' class="text-blue-600 hover:text-blue-700"><i class="fas fa-pen"></i></button>
                                    <form action="{{ route('admin.chatbotwizard.delete-service', [$company, $service]) }}" method="POST" class="inline" onsubmit="return confirm('Delete this service?');">
                                        @csrf @method('DELETE')
                                        <input type="hidden" name="active_tab" value="services">
                                        <button type="submit" class="text-red-600 hover:text-red-700"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 mb-2">{{ Str::limit($service->short_description, 80) }}</p>
                            <div class="flex gap-2">
                                @if($service->is_active)
                                <span class="inline-flex items-center gap-1 bg-green-50 text-green-700 px-2 py-1 rounded-full text-xs"><i class="fas fa-circle-check"></i>Active</span>
                                @endif
                            </div>
                        </div>
                        @empty
                        <div class="col-span-3 text-center py-8">
                            <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
                            <p class="text-gray-500">No services added yet</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>{{-- end #tab-content --}}
    </div>
</div>

{{-- ═══════════════════════════════════════════
     MODALS
═══════════════════════════════════════════ --}}

<!-- Config Modal -->
<div id="configModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <form action="{{ route('admin.chatbotwizard.save-config', $company) }}" method="POST">
            @csrf
            <input type="hidden" name="config_id" id="config_id">
            <input type="hidden" name="active_tab" value="configs">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-xl font-bold text-gray-800"><span id="configModalTitle">Add Message</span></h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Config Key *</label>
                    <input type="text" name="config_key" id="config_key" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500" placeholder="e.g., welcome_message">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Category</label>
                    <select name="category" id="category" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="messages">Messages</option>
                        <option value="tracking">Tracking</option>
                        <option value="support">Support</option>
                        <option value="info">Info</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Message Content *</label>
                    <textarea name="config_value" id="config_value" rows="6" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500" placeholder="Enter your message here..."></textarea>
                </div>
            </div>
            <div class="p-6 bg-gray-50 flex justify-end gap-3">
                <button type="button" onclick="closeConfigModal()" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700"><i class="fas fa-save mr-2"></i>Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Button Template Modal -->
<div id="buttonModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
        <form action="{{ route('admin.chatbotwizard.save-button', $company) }}" method="POST">
            @csrf
            <input type="hidden" name="button_id" id="button_id">
            <input type="hidden" name="active_tab" value="buttons">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-xl font-bold text-gray-800"><span id="buttonModalTitle">Add Button Template</span></h3>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Template Name *</label>
                        <input type="text" name="template_name" id="template_name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Context *</label>
                        <input type="text" name="context" id="btn_context" required class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="e.g., default, products">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Priority</label>
                        <input type="number" name="priority" id="btn_priority" value="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="flex items-center gap-2 cursor-pointer mt-8">
                            <input type="checkbox" name="is_active" id="btn_is_active" value="1" checked class="w-5 h-5 text-green-600 rounded">
                            <span class="text-sm font-semibold text-gray-700">Active</span>
                        </label>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Buttons (JSON) *</label>
                    <textarea name="buttons" id="buttons" rows="8" required class="w-full px-4 py-2 border border-gray-300 rounded-lg font-mono text-sm" placeholder='[{"label":"📦 Products","value":"products","order":1}]'></textarea>
                    <p class="text-xs text-gray-500 mt-1">JSON array format required</p>
                </div>
            </div>
            <div class="p-6 bg-gray-50 flex justify-end gap-3">
                <button type="button" onclick="closeButtonModal()" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"><i class="fas fa-save mr-2"></i>Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Product Modal -->
<div id="productModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
        <form action="{{ route('admin.chatbotwizard.save-product', $company) }}" method="POST">
            @csrf
            <input type="hidden" name="product_id" id="product_id">
            <input type="hidden" name="active_tab" value="products">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-xl font-bold text-gray-800"><span id="productModalTitle">Add Product</span></h3>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Product Name *</label>
                        <input type="text" name="name" id="product_name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Slug *</label>
                        <input type="text" name="slug" id="product_slug" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Short Description *</label>
                    <textarea name="short_description" id="product_short_desc" rows="2" required class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Full Description</label>
                    <textarea name="full_description" id="product_full_desc" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Icon</label>
                        <input type="text" name="icon" id="product_icon" class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="📦">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Display Order</label>
                        <input type="number" name="display_order" id="product_order" value="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div class="flex items-end gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="demo_available" id="product_demo" value="1" class="w-5 h-5 text-orange-600 rounded">
                            <span class="text-sm font-semibold text-gray-700">Demo</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_featured" id="product_featured" value="1" class="w-5 h-5 text-yellow-600 rounded">
                            <span class="text-sm font-semibold text-gray-700">Featured</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" id="product_active" value="1" checked class="w-5 h-5 text-green-600 rounded">
                            <span class="text-sm font-semibold text-gray-700">Active</span>
                        </label>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Keywords (JSON Array) *</label>
                    <textarea name="keywords" id="product_keywords" rows="2" required class="w-full px-4 py-2 border border-gray-300 rounded-lg font-mono text-sm" placeholder='["product","software"]'></textarea>
                </div>
            </div>
            <div class="p-6 bg-gray-50 flex justify-end gap-3">
                <button type="button" onclick="closeProductModal()" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700"><i class="fas fa-save mr-2"></i>Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Service Modal -->
<div id="serviceModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
        <form action="{{ route('admin.chatbotwizard.save-service', $company) }}" method="POST">
            @csrf
            <input type="hidden" name="service_id" id="service_id">
            <input type="hidden" name="active_tab" value="services">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-xl font-bold text-gray-800"><span id="serviceModalTitle">Add Service</span></h3>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Service Name *</label>
                        <input type="text" name="name" id="service_name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Slug *</label>
                        <input type="text" name="slug" id="service_slug" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Short Description *</label>
                    <textarea name="short_description" id="service_short_desc" rows="2" required class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Full Description</label>
                    <textarea name="full_description" id="service_full_desc" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Icon</label>
                        <input type="text" name="icon" id="service_icon" class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="🔧">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Display Order</label>
                        <input type="number" name="display_order" id="service_order" value="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div class="flex items-end gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="enquiry_available" id="service_enquiry" value="1" checked class="w-5 h-5 text-indigo-600 rounded">
                            <span class="text-sm font-semibold text-gray-700">Enquiry</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" id="service_active" value="1" checked class="w-5 h-5 text-green-600 rounded">
                            <span class="text-sm font-semibold text-gray-700">Active</span>
                        </label>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Keywords (JSON Array) *</label>
                    <textarea name="keywords" id="service_keywords" rows="2" required class="w-full px-4 py-2 border border-gray-300 rounded-lg font-mono text-sm" placeholder='["service","website"]'></textarea>
                </div>
            </div>
            <div class="p-6 bg-gray-50 flex justify-end gap-3">
                <button type="button" onclick="closeServiceModal()" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"><i class="fas fa-save mr-2"></i>Save</button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════════
     Integration Modal  (Add + Edit)
     services stored as raw JSON textarea
     so complex nested objects are preserved
══════════════════════════════════════════ -->
<div id="integrationModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <form id="integrationForm" method="POST" action="{{ route('admin.chatbotwizard.integrations.save', $company) }}">
            @csrf

            {{-- DB id for edit mode; empty for create --}}
            <input type="hidden" name="integration_id" id="integration_id">

            <div class="p-6 border-b border-gray-200">
                <h3 class="text-xl font-bold text-gray-800">
                    <span id="integrationModalTitle">Add Integration</span>
                </h3>
            </div>

            <div class="p-6 space-y-6">

                <!-- Integration Key -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Integration Key *</label>
                    <input type="text" name="integration_key" id="integration_key"
                        placeholder="danabook"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500"
                        required />
                    <p class="text-xs text-gray-400 mt-1">Unique name used in flow data_config. eg: danabook, zoho</p>
                </div>

                <!-- Auth Section -->
                <div class="border border-gray-200 rounded-lg p-4">
                    <h4 class="text-sm font-bold text-gray-700 mb-3">
                        <i class="fas fa-lock text-pink-500 mr-1"></i> Auth Service
                    </h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Auth Base URL *</label>
                            <input type="url" name="auth_base_url" id="integration_auth_base_url"
                                placeholder="https://userhub.example.com"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500"
                                required />
                            <p class="text-xs text-gray-400 mt-1">The microservice that handles login</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Auth Endpoint *</label>
                            <input type="text" name="auth_endpoint" id="integration_auth_endpoint"
                                placeholder="/api/auth/login"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500"
                                required />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Token Path *</label>
                            <input type="text" name="token_path" id="integration_token_path"
                                placeholder="data.access_token"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500"
                                required />
                            <p class="text-xs text-gray-400 mt-1">eg: data.access_token, token</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Identifier Field</label>
                            <input type="text" name="auth_identifier_field" id="integration_auth_identifier_field"
                                placeholder="email" value="email"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500" />
                            <p class="text-xs text-gray-400 mt-1">Field sent as identifier header</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Token TTL (seconds)</label>
                            <input type="number" name="token_ttl" id="integration_token_ttl"
                                value="3600"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500" />
                            <p class="text-xs text-gray-400 mt-1">Default: 3600 (1 hour)</p>
                        </div>
                    </div>
                </div>

                <!-- Microservices — raw JSON textarea to preserve nested objects -->
                <div class="border border-gray-200 rounded-lg p-4">
                    <h4 class="text-sm font-bold text-gray-700 mb-2">
                        <i class="fas fa-server text-pink-500 mr-1"></i> Microservices (JSON)
                    </h4>
                    <p class="text-xs text-gray-400 mb-3">
                        Enter a JSON object where each key is a service name and the value is its full config
                        (base_url, endpoint, method, body, response_template, etc.)
                    </p>
                    <textarea
                        name="services_json"
                        id="integration_services_json"
                        rows="14"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg font-mono text-xs focus:ring-2 focus:ring-pink-500"
                        placeholder='{
  "cash_balance": {
    "base_url": "https://dashboardhub.example.com",
    "endpoint": "/api/v1/dashboardvalues",
    "method": "post",
    "body": { "api": "/api/v1/dashboardvalues", "apiargument": "cashbalance" },
    "response_template": "Your Cash Balance: {data_list}",
    "list_field": "data",
    "list_template": "- {name}: {value}"
  }
}'></textarea>
                    <p class="text-xs text-red-500 mt-1 hidden" id="services_json_error">
                        <i class="fas fa-exclamation-circle mr-1"></i>Invalid JSON — please fix before saving.
                    </p>
                </div>

            </div>

            <div class="p-6 bg-gray-50 flex justify-end gap-3">
                <button type="button" onclick="closeIntegrationModal()"
                    class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Cancel</button>
                <button type="submit"
                    class="px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700">
                    <i class="fas fa-save mr-2"></i>Save
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    .tab-btn { color: #6b7280; border-bottom: 2px solid transparent; }
    .tab-btn.active { color: #4f46e5; border-bottom-color: #4f46e5; background: #f9fafb; }
    .tab-content { display: none; }
    .tab-content.active { display: block; }
</style>

<script>
// ── ACTIVE TAB RESTORE ─────────────────────────────────────────
const activeTab = '{{ session("active_tab", "configs") }}';
document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
document.querySelector(`.tab-btn[data-tab="${activeTab}"]`).classList.add('active');
document.querySelector(`.tab-content[data-tab="${activeTab}"]`).classList.add('active');

// ── TAB SWITCHING ──────────────────────────────────────────────
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const tab = btn.dataset.tab;
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        document.querySelector(`.tab-content[data-tab="${tab}"]`).classList.add('active');
    });
});

// ── CONFIG ─────────────────────────────────────────────────────
function openConfigModal() {
    document.getElementById('configModalTitle').textContent = 'Add Message';
    document.getElementById('config_id').value = '';
    document.getElementById('config_key').value = '';
    document.getElementById('category').value = 'messages';
    document.getElementById('config_value').value = '';
    document.getElementById('configModal').classList.remove('hidden');
}
function editConfig(btn) {
    const data = JSON.parse(btn.getAttribute('data-template'));
    document.getElementById('configModalTitle').textContent = 'Edit Message';
    document.getElementById('config_id').value = data.id;
    document.getElementById('config_key').value = data.key;
    document.getElementById('category').value = data.category || 'messages';
    document.getElementById('config_value').value = data.value;
    document.getElementById('configModal').classList.remove('hidden');
}
function closeConfigModal() { document.getElementById('configModal').classList.add('hidden'); }

// ── BUTTONS ────────────────────────────────────────────────────
function openButtonModal() {
    document.getElementById('buttonModalTitle').textContent = 'Add Button Template';
    document.getElementById('button_id').value = '';
    document.getElementById('template_name').value = '';
    document.getElementById('btn_context').value = '';
    document.getElementById('btn_priority').value = '0';
    document.getElementById('btn_is_active').checked = true;
    document.getElementById('buttons').value = '';
    document.getElementById('buttonModal').classList.remove('hidden');
}
function editButtonTemplate(btn) {
    const data = JSON.parse(btn.getAttribute('data-template'));
    document.getElementById('buttonModalTitle').textContent = 'Edit Button Template';
    document.getElementById('button_id').value = data.id;
    document.getElementById('template_name').value = data.template_name;
    document.getElementById('btn_context').value = data.context;
    document.getElementById('btn_priority').value = data.priority;
    document.getElementById('btn_is_active').checked = data.is_active == 1;
    document.getElementById('buttons').value = JSON.stringify(data.buttons, null, 2);
    document.getElementById('buttonModal').classList.remove('hidden');
}
function closeButtonModal() { document.getElementById('buttonModal').classList.add('hidden'); }

// ── FLOWS ──────────────────────────────────────────────────────
function openFlowModal() {
    window.location.href = "{{ route('admin.flows.create', ['company' => $company->id]) }}";
}

// ── PRODUCTS ───────────────────────────────────────────────────
function openProductModal() {
    document.getElementById('productModalTitle').textContent = 'Add Product';
    document.getElementById('product_id').value = '';
    document.getElementById('product_name').value = '';
    document.getElementById('product_slug').value = '';
    document.getElementById('product_short_desc').value = '';
    document.getElementById('product_full_desc').value = '';
    document.getElementById('product_icon').value = '';
    document.getElementById('product_order').value = '0';
    document.getElementById('product_demo').checked = false;
    document.getElementById('product_featured').checked = false;
    document.getElementById('product_active').checked = true;
    document.getElementById('product_keywords').value = '';
    document.getElementById('productModal').classList.remove('hidden');
}
function editProduct(btn) {
    const data = JSON.parse(btn.getAttribute('data-template'));
    document.getElementById('productModalTitle').textContent = 'Edit Product';
    document.getElementById('product_id').value = data.id;
    document.getElementById('product_name').value = data.name;
    document.getElementById('product_slug').value = data.slug;
    document.getElementById('product_short_desc').value = data.short_description;
    document.getElementById('product_full_desc').value = data.full_description;
    document.getElementById('product_icon').value = data.icon;
    document.getElementById('product_order').value = data.display_order;
    document.getElementById('product_demo').checked = data.demo_available == 1;
    document.getElementById('product_featured').checked = data.is_featured == 1;
    document.getElementById('product_active').checked = data.is_active == 1;
    document.getElementById('product_keywords').value = JSON.stringify(data.keywords);
    document.getElementById('productModal').classList.remove('hidden');
}
function closeProductModal() { document.getElementById('productModal').classList.add('hidden'); }

// ── SERVICES ───────────────────────────────────────────────────
function openServiceModal() {
    document.getElementById('serviceModalTitle').textContent = 'Add Service';
    document.getElementById('service_id').value = '';
    document.getElementById('service_name').value = '';
    document.getElementById('service_slug').value = '';
    document.getElementById('service_short_desc').value = '';
    document.getElementById('service_full_desc').value = '';
    document.getElementById('service_icon').value = '';
    document.getElementById('service_order').value = '0';
    document.getElementById('service_enquiry').checked = true;
    document.getElementById('service_active').checked = true;
    document.getElementById('service_keywords').value = '';
    document.getElementById('serviceModal').classList.remove('hidden');
}
function editService(btn) {
    const data = JSON.parse(btn.getAttribute('data-template'));
    document.getElementById('serviceModalTitle').textContent = 'Edit Service';
    document.getElementById('service_id').value = data.id;
    document.getElementById('service_name').value = data.name;
    document.getElementById('service_slug').value = data.slug;
    document.getElementById('service_short_desc').value = data.short_description;
    document.getElementById('service_full_desc').value = data.full_description;
    document.getElementById('service_icon').value = data.icon;
    document.getElementById('service_order').value = data.display_order;
    document.getElementById('service_enquiry').checked = data.enquiry_available == 1;
    document.getElementById('service_active').checked = data.is_active == 1;
    document.getElementById('service_keywords').value = JSON.stringify(data.keywords);
    document.getElementById('serviceModal').classList.remove('hidden');
}
function closeServiceModal() { document.getElementById('serviceModal').classList.add('hidden'); }

// ── INTEGRATIONS ───────────────────────────────────────────────

/** Open in ADD mode — blank form */
function openIntegrationModal() {
    document.getElementById('integrationModalTitle').textContent = 'Add Integration';
    document.getElementById('integration_id').value = '';
    document.getElementById('integration_key').value = '';
    document.getElementById('integration_auth_base_url').value = '';
    document.getElementById('integration_auth_endpoint').value = '';
    document.getElementById('integration_token_path').value = '';
    document.getElementById('integration_auth_identifier_field').value = 'email';
    document.getElementById('integration_token_ttl').value = '3600';
    document.getElementById('integration_services_json').value = '{}';
    document.getElementById('services_json_error').classList.add('hidden');
    document.getElementById('integrationModal').classList.remove('hidden');
}

/**
 * Open in EDIT mode.
 * services is a plain object in DB (possibly nested like {key:{base_url,endpoint,...}})
 * — we just pretty-print it into the textarea so users can edit the full JSON.
 */
function editIntegration(btn) {
    const data = JSON.parse(btn.getAttribute('data-template'));

    document.getElementById('integrationModalTitle').textContent = 'Edit Integration';
    document.getElementById('integration_id').value = data.id;
    document.getElementById('integration_key').value = data.integration_key;
    document.getElementById('integration_auth_base_url').value = data.auth_base_url;
    document.getElementById('integration_auth_endpoint').value = data.auth_endpoint;
    document.getElementById('integration_token_path').value = data.token_path;
    document.getElementById('integration_auth_identifier_field').value = data.auth_identifier_field ?? 'email';
    document.getElementById('integration_token_ttl').value = data.token_ttl ?? 3600;

    // Pretty-print the services object — works for both simple {"key":"url"} and
    // nested {"key":{"base_url":"...","endpoint":"...",...}} structures
    const services = data.services ?? {};
    document.getElementById('integration_services_json').value = JSON.stringify(services, null, 2);
    document.getElementById('services_json_error').classList.add('hidden');

    document.getElementById('integrationModal').classList.remove('hidden');
}

function closeIntegrationModal() {
    document.getElementById('integrationModal').classList.add('hidden');
}

/** Validate JSON on submit */
document.getElementById('integrationForm').addEventListener('submit', function (e) {
    const ta     = document.getElementById('integration_services_json');
    const errEl  = document.getElementById('services_json_error');
    const val    = ta.value.trim();

    // Allow empty / blank textarea (controller will default to {})
    if (val === '' || val === '{}') {
        errEl.classList.add('hidden');
        return;
    }

    try {
        const parsed = JSON.parse(val);
        if (typeof parsed !== 'object' || Array.isArray(parsed)) {
            throw new Error('Must be a JSON object');
        }
        errEl.classList.add('hidden');
    } catch (err) {
        errEl.classList.remove('hidden');
        e.preventDefault();
    }
});

// ── ESC key ────────────────────────────────────────────────────
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeConfigModal();
        closeButtonModal();
        closeProductModal();
        closeServiceModal();
        closeIntegrationModal();
    }
});
</script>

@endsection