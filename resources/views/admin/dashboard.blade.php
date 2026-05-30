@extends('admin.layouts.admin')

@section('title', 'Chatbot Dashboard')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900">Chatbot Dashboard</h1>
            <p class="text-gray-600 mt-2">Manage your chatbot flows and conversations</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white overflow-hidden shadow-sm rounded-lg border-l-4 border-blue-500">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Active Flows</p>
                            <h3 class="text-3xl font-bold text-gray-800 mt-2">{{ $activeFlows ?? 0 }}</h3>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-lg">
                            <i class="fas fa-sitemap text-blue-600 text-2xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-lg border-l-4 border-green-500">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Total Messages</p>
                            <h3 class="text-3xl font-bold text-gray-800 mt-2">{{ $totalMessages ?? 0 }}</h3>
                        </div>
                        <div class="bg-green-100 p-3 rounded-lg">
                            <i class="fas fa-comments text-green-600 text-2xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-lg border-l-4 border-purple-500">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Today's Messages</p>
                            <h3 class="text-3xl font-bold text-gray-800 mt-2">{{ $messagesToday ?? 0 }}</h3>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-lg">
                            <i class="fas fa-envelope text-purple-600 text-2xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-lg border-l-4 border-orange-500">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Completion Rate</p>
                            <h3 class="text-3xl font-bold text-gray-800 mt-2">{{ $completionRate ?? 0 }}%</h3>
                        </div>
                        <div class="bg-orange-100 p-3 rounded-lg">
                            <i class="fas fa-check-circle text-orange-600 text-2xl"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chatbot Flows Table -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-900">Chatbot Flows</h2>
                    {{-- Dashboard-ല്‍ company context ഇല്ലാത്തതുകൊണ്ട് companies page-ലേക്ക് --}}
                    <a href="{{ route('admin.companies.index') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">
                        <i class="fas fa-plus mr-2"></i>New Flow
                    </a>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                @if($flows && $flows->count() > 0)
                    <table class="min-w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Flow Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Priority</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($flows as $flow)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center mr-3">
                                                <i class="fas fa-diagram-project text-indigo-600"></i>
                                            </div>
                                            <span class="font-medium text-gray-900">{{ $flow->flow_name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-gray-600 text-sm">{{ str_replace('_', ' ', ucfirst($flow->flow_type)) }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-sm">{{ $flow->priority }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($flow->is_active)
                                            <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm">Active</span>
                                        @else
                                            <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ $flow->created_at ? $flow->created_at->format('M d, Y') : 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <a href="{{ route('admin.flows.edit', ['company' => $flow->company_id, 'flow' => $flow->id]) }}" class="text-blue-600 hover:text-blue-900 mr-3">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.flows.destroy', ['company' => $flow->company_id, 'flow' => $flow->id]) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @if(method_exists($flows, 'links'))
                        <div class="px-6 py-4 border-t border-gray-200">
                            {{ $flows->links() }}
                        </div>
                    @endif
                @else
                    <div class="px-6 py-12 text-center">
                        <i class="fas fa-inbox text-gray-400 text-4xl mb-4 block"></i>
                        <p class="text-gray-600">No flows created yet.</p>
                        <a href="{{ route('admin.companies.index') }}" class="text-indigo-600 hover:text-indigo-900 mt-2 inline-block">
                            Select a company to create your first flow
                        </a>
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endsection