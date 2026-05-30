<?php

namespace App\Http\Controllers;

use App\Models\ChatbotConfig;
use App\Models\Company;
use Illuminate\Http\Request;

class ChatbotConfigController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, $companyId = null)
    {
        dd($request);
        if ($companyId) {
            // Show single company's config
            $company = Company::with('chatbotConfigs')->findOrFail($companyId);
            $companies = collect([$company]); // Wrap in collection
        } else {
            // Show all companies
            $companies = Company::with('chatbotConfigs')
                ->orderBy('name')
                ->get();
        }

        return view('admin.chatbotconfigs.index', compact('companies'));
    }

    /**
     * Show the form for creating chatbot config
     * Resource route expects no parameters for create()
     */
    public function create(Request $request)
    {
        // Get company_id from query parameter
        $companyId = $request->query('company_id');

        if (!$companyId) {
            return redirect()->route('admin.companies.index')
                ->with('error', 'Company ID is required');
        }

        $company = Company::findOrFail($companyId);

        // Get all configs for this company as key-value pairs
        $configs = ChatbotConfig::where('company_id', $company->id)
            ->pluck('config_value', 'config_key')
            ->toArray();

        return view('admin.chatbotconfigs.create', compact('company', 'configs'));
    }

    /**
     * Store chatbot config in storage
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'welcome_message' => 'required|string',
            'goodbye_message' => 'required|string',
            'error_message' => 'required|string',
            'demo_success_message' => 'nullable|string',
            'enquiry_success_message' => 'nullable|string',
            'tracking_prompt' => 'nullable|string',
        ]);

        $company = Company::findOrFail($validated['company_id']);

        try {
            // Remove company_id from validated data
            $companyId = $validated['company_id'];
            unset($validated['company_id']);

            // Update or create each config
            foreach ($validated as $key => $value) {
                // Skip if value is null
                if ($value === null) {
                    continue;
                }

                ChatbotConfig::updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'config_key' => $key,
                    ],
                    [
                        'config_value' => $value,
                        'category' => 'messages',
                    ]
                );
            }

            return redirect()
                ->route('admin.chatbotconfigs.create', ['company_id' => $companyId])
                ->with('success', 'Chatbot configuration saved successfully!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to save configuration: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $company = Company::findOrFail($id);

        $chatbotconfigs = ChatbotConfig::where('company_id', $company->id)
           
            ->get();
       // dd($chatbotconfigs);
        return view('admin.chatbotconfigs.index', compact('company', 'chatbotconfigs'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
