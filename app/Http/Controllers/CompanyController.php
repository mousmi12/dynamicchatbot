<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $companies = Company::withCount('chatbotFlows')->paginate(10);
        return view('admin.companies.index', compact('companies'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.companies.create');
    }

    /**
     * Store a newly created resource in storage.
     */


    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'slug' => 'nullable|string|unique:companies',
                'description' => 'nullable|string',
                'website' => 'nullable|url',
                'notification_email' => 'required|email',
                'phone_locations.*' => 'required|string|max:255',
                'phone_numbers.*' => 'required|string|max:255',
                'email_addresses.*' => 'nullable|email',
                'bot_name' => 'required|string|max:255',
                'primary_color' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
                'secondary_color' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
                'ai_provider' => 'required|string|in:groq,openai,anthropic',
                'ai_model' => 'required|string|max:255',
                'ai_temperature' => 'required|numeric|min:0|max:1',
                'ai_max_tokens' => 'required|integer|min:50|max:4096',
                'is_active' => 'nullable|boolean',
            ]);

            // Convert phone numbers array to JSON object
            $phoneNumbers = [];
            if (!empty($request->phone_locations) && !empty($request->phone_numbers)) {
                foreach ($request->phone_locations as $index => $location) {
                    if (!empty($location) && !empty($request->phone_numbers[$index])) {
                        $phoneNumbers[$location] = $request->phone_numbers[$index];
                    }
                }
            }

            // Convert email addresses array to JSON array
            $emailAddresses = array_filter($request->email_addresses ?? []);

            // Auto-generate slug if not provided
            $slug = !empty($validated['slug'])
                ? $validated['slug']
                : Str::slug($validated['name']);

            // Create company with JSON data
            $company = Company::create([
                'name' => $validated['name'],
                'slug' => $slug,
                'description' => $validated['description'] ?? null,
                'website' => $validated['website'] ?? null,
                'notification_email' => $validated['notification_email'],
                'phone_numbers' => json_encode($phoneNumbers),
                'email_addresses' => json_encode(array_values($emailAddresses)),
                'bot_name' => $validated['bot_name'],
                'primary_color' => $validated['primary_color'],
                'secondary_color' => $validated['secondary_color'],
                'ai_provider' => $validated['ai_provider'],
                'ai_model' => $validated['ai_model'],
                'ai_temperature' => $validated['ai_temperature'],
                'ai_max_tokens' => $validated['ai_max_tokens'],
                'is_active' => $request->boolean('is_active', true),
            ]);
return redirect()->route('admin.companies.settings.edit', $company)
    ->with('success', 'Company created! Now configure the settings.');
            // return redirect()->route('admin.companies.index')
            //     ->with('success', 'Company created successfully');
        } catch (ValidationException $e) {
          //  dd($e);
            return redirect()->back()
                ->withInput()
                ->withErrors($e->errors());
        } catch (\Exception $e) {
           //  dd($e);
            return redirect()->back()
                ->withInput()
                ->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }




    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Company $company)
    {
        return view('admin.companies.create', compact('company'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Company $company)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'slug' => 'nullable|string|unique:companies,slug,' . $company->id,
                'description' => 'nullable|string',
                'website' => 'nullable|url',
                'notification_email' => 'required|email',
                'phone_locations.*' => 'required|string|max:255',
                'phone_numbers.*' => 'required|string|max:255',
                'email_addresses.*' => 'nullable|email',
                'bot_name' => 'required|string|max:255',
                'primary_color' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
                'secondary_color' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
                'ai_provider' => 'required|string|in:groq,openai,anthropic',
                'ai_model' => 'required|string|max:255',
                'ai_temperature' => 'required|numeric|min:0|max:1',
                'ai_max_tokens' => 'required|integer|min:50|max:4096',
                'is_active' => 'nullable|boolean',
            ]);

            // Convert phone numbers array to JSON object
            $phoneNumbers = [];
            if (!empty($request->phone_locations) && !empty($request->phone_numbers)) {
                foreach ($request->phone_locations as $index => $location) {
                    if (!empty($location) && !empty($request->phone_numbers[$index])) {
                        $phoneNumbers[$location] = $request->phone_numbers[$index];
                    }
                }
            }

            // Convert email addresses array to JSON array
            $emailAddresses = array_filter($request->email_addresses ?? []);

            // Auto-generate slug if not provided
            $slug = !empty($validated['slug'])
                ? $validated['slug']
                : Str::slug($validated['name']);

            // Update company with JSON data
            $company->update([
                'name' => $validated['name'],
                'slug' => $slug,
                'description' => $validated['description'] ?? null,
                'website' => $validated['website'] ?? null,
                'notification_email' => $validated['notification_email'],
                'phone_numbers' => json_encode($phoneNumbers),
                'email_addresses' => json_encode(array_values($emailAddresses)),
                'bot_name' => $validated['bot_name'],
                'primary_color' => $validated['primary_color'],
                'secondary_color' => $validated['secondary_color'],
                'ai_provider' => $validated['ai_provider'],
                'ai_model' => $validated['ai_model'],
                'ai_temperature' => $validated['ai_temperature'],
                'ai_max_tokens' => $validated['ai_max_tokens'],
                'is_active' => $request->boolean('is_active', true),
            ]);
return redirect()->route('admin.companies.settings.edit', $company)
    ->with('success', 'Company created! Now configure the settings.');
            // return redirect()->route('admin.companies.index')
            //     ->with('success', 'Company updated successfully');
        } catch (ValidationException $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors($e->errors());
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company)
    {
        $company->delete();

        return redirect()->route('companies.index')
            ->with('success', 'Company deleted successfully');
    }
}