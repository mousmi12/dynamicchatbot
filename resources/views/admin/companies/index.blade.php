@extends('admin.layouts.admin')

@section('content')
<div class="p-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-3xl font-bold text-gray-800">Companies</h2>
                <p class="text-gray-600 text-sm mt-1">Manage all companies and their chatbots</p>
            </div>
            <a href="{{ route('admin.companies.create') }}" class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition flex items-center">
                <i class="fas fa-plus mr-2"></i>Add Company
            </a>
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

        <!-- Companies Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase">Company</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase">Slug</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase">Config Status</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase">Status</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($companies as $company)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center font-bold text-sm">
                                    {{ strtoupper(substr($company->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800">{{ $company->name }}</p>
                                    <p class="text-gray-500 text-sm">{{ Str::limit($company->description, 40) }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <code class="bg-gray-100 text-gray-800 px-3 py-1 rounded text-sm">{{ $company->slug }}</code>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-2">
                                <span class="inline-flex items-center gap-1 bg-purple-50 text-purple-700 px-2 py-1 rounded-full text-xs font-medium">
                                    <i class="fas fa-cog"></i>
                                    {{ $company->chatbotConfigs()->count() }} configs
                                </span>
                                <span class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 px-2 py-1 rounded-full text-xs font-medium">
                                    <i class="fas fa-diagram-project"></i>
                                    {{ $company->chatbotFlows()->count() }} flows
                                </span>
                                <span class="inline-flex items-center gap-1 bg-green-50 text-green-700 px-2 py-1 rounded-full text-xs font-medium">
                                    <i class="fas fa-hand-pointer"></i>
                                    {{ $company->buttonTemplates()->count() }} buttons
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($company->is_active)
                            <span class="inline-flex items-center gap-1 bg-green-50 text-green-700 px-3 py-1 rounded-full text-sm font-medium">
                                <i class="fas fa-circle-check"></i>Active
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1 bg-gray-50 text-gray-700 px-3 py-1 rounded-full text-sm font-medium">
                                <i class="fas fa-circle-xmark"></i>Inactive
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <!-- NEW: Chatbot Wizard Button (Main Action) -->
                                <a href="{{ route('admin.chatbotwizard.index', $company) }}"
                                    class="bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white px-4 py-2 rounded-lg transition flex items-center gap-2 text-sm font-semibold shadow-md"
                                    title="Chatbot Setup Wizard">
                                    <i class="fas fa-magic"></i>
                                    <span>Setup Wizard</span>
                                </a>

                                <!-- Edit Company Button -->
                                <a href="{{ route('admin.companies.edit', $company) }}"
                                    class="bg-blue-100 hover:bg-blue-200 text-blue-700 px-3 py-2 rounded-lg transition"
                                    title="Edit Company">
                                    <i class="fas fa-pen"></i>
                                </a>

                                <!-- Delete Button -->
                                <form action="{{ route('admin.companies.destroy', $company) }}" method="POST" class="inline"
                                    onsubmit="return confirm('Are you sure you want to delete this company? This will delete all associated data including flows, configs, products, and services.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="bg-red-100 hover:bg-red-200 text-red-700 px-3 py-2 rounded-lg transition"
                                        title="Delete Company">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
                                <p class="text-gray-500 font-semibold">No companies found</p>
                                <p class="text-gray-400 text-sm">Create your first company to get started</p>
                                <a href="{{ route('admin.companies.create') }}" class="mt-4 bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition">
                                    <i class="fas fa-plus mr-2"></i>Create Company
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Info Card -->
        <div class="mt-8 bg-gradient-to-br from-indigo-50 to-purple-50 rounded-xl shadow-sm border border-indigo-200 p-6">
            <div class="flex items-center gap-2 mb-4">
                <i class="fas fa-lightbulb text-indigo-600"></i>
                <h4 class="font-bold text-gray-800">Quick Setup Guide</h4>
            </div>
            <ul class="space-y-2 text-sm text-gray-700">
                <li class="flex items-start gap-2">
                    <i class="fas fa-check-circle text-green-600 mt-0.5"></i>
                    <span><strong>Setup Wizard</strong> - Use the "Setup Wizard" button to configure all chatbot settings in one place</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class="fas fa-check-circle text-green-600 mt-0.5"></i>
                    <span><strong>Messages</strong> - Configure welcome, goodbye, error, and success messages</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class="fas fa-check-circle text-green-600 mt-0.5"></i>
                    <span><strong>Flows</strong> - Create conversation flows for data collection and queries</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class="fas fa-check-circle text-green-600 mt-0.5"></i>
                    <span><strong>Buttons</strong> - Define button templates for different contexts</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class="fas fa-check-circle text-green-600 mt-0.5"></i>
                    <span><strong>Products & Services</strong> - Add products and services for chatbot to reference</span>
                </li>
            </ul>
        </div>
    </div>
</div>
@endsection