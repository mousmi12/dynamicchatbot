<?php

namespace App\Http\Controllers;

use App\Models\ChatbotFlow;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class FlowController extends Controller
{

    // public function index(Company $company)
    // {
    //     $flows = $company->chatbotFlows()->orderBy('priority', 'desc')->get();
    //     return view('admin.flows.index', compact('company', 'flows'));
    // }
    public function index() {}

    public function show($id)
    {
        $company = Company::findOrFail($id);

        $flows = ChatbotFlow::where('company_id', $company->id)
            ->orderBy('priority', 'desc')
            ->get();

        return view('admin.flows.index', compact('company', 'flows'));
    }

    public function create(Company $company)
    {
        return view('admin.flows.create', compact('company'));
    }

    public function store(Request $request, Company $company)
    {
        try {
            $validated = $request->validate([
                'flow_name'          => 'required|string|max:255',
                'flow_type'          => 'required|string|in:form_collection,data_query,quick_reply,api_auth',
                'triggers'           => 'nullable|array',
                'triggers.*'         => 'nullable|string|max:255',
                'steps'              => 'nullable|array',
                'steps.*.message'    => 'required|string|max:1000',
                'steps.*.cache_key'  => 'required|string|max:255',
                'steps.*.field_name' => 'required|string|max:255',
                'data_config'        => 'nullable|string',
                'priority'           => 'nullable|integer|min:0',
            ]);

            // Filter empty triggers
            $triggers = array_values(array_filter($request->triggers ?? [], fn($t) => !empty(trim($t))));

            // Process steps
            $steps = [];
            foreach ($request->steps ?? [] as $step) {
                if (!empty($step['message']) && !empty($step['cache_key']) && !empty($step['field_name'])) {
                    $steps[] = [
                        'message'    => trim($step['message']),
                        'cache_key'  => trim($step['cache_key']),
                        'field_name' => trim($step['field_name']),
                    ];
                }
            }

            if (empty($triggers)) {
                return redirect()->back()->withInput()->with('error', 'At least one trigger is required');
            }

            if (empty($steps)) {
                return redirect()->back()->withInput()->with('error', 'At least one step is required');
            }

            // Process data_config JSON
            $dataConfig = trim($request->data_config ?? '{}');
            if (!empty($dataConfig) && $dataConfig !== '{}') {
                $decoded = json_decode($dataConfig, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return redirect()->back()->withInput()->with('error', 'Invalid JSON in data configuration');
                }
                $dataConfigToStore = $decoded;
            } else {
                $dataConfigToStore = [];
            }

            $flow = ChatbotFlow::create([
                'company_id' => $company->id,
                'flow_name'  => $validated['flow_name'],
                'flow_type'  => $validated['flow_type'],
                'triggers'   => $triggers,
                'steps'      => $steps,
                'data_config'=> $dataConfigToStore,
                'priority'   => $validated['priority'] ?? 0,
                'is_active'  => $request->boolean('is_active', true),
            ]);

            return redirect()->route('admin.flows.index', $company->id)
                ->with('success', 'Flow "' . $flow->flow_name . '" created successfully!');

        } catch (ValidationException $e) {
            return redirect()->back()->withInput()->withErrors($e->errors())->with('error', 'Validation failed.');
        } catch (\Exception $e) {
            Log::error('Flow creation error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Error creating flow: ' . $e->getMessage());
        }
    }

    public function edit(Company $company, ChatbotFlow $flow)
    {
        if ($flow->company_id !== $company->id) {
            abort(404);
        }

        return view('admin.flows.create', compact('company', 'flow'));
    }

    public function update(Request $request, Company $company, ChatbotFlow $flow)
    {
        if ($flow->company_id !== $company->id) {
            abort(404);
        }

        try {
            $validated = $request->validate([
                'flow_name'          => 'required|string|max:255',
                'flow_type'          => 'required|string|in:form_collection,data_query,quick_reply,api_auth',
                'triggers'           => 'nullable|array',
                'triggers.*'         => 'nullable|string|max:255',
                'steps'              => 'nullable|array',
                'steps.*.message'    => 'required|string|max:1000',
                'steps.*.cache_key'  => 'required|string|max:255',
                'steps.*.field_name' => 'required|string|max:255',
                'data_config'        => 'nullable|string',
                'priority'           => 'nullable|integer|min:0',
            ]);

            // Filter empty triggers
            $triggers = array_values(array_filter($request->triggers ?? [], fn($t) => !empty(trim($t))));

            // Process steps
            $steps = [];
            foreach ($request->steps ?? [] as $step) {
                if (!empty($step['message']) && !empty($step['cache_key']) && !empty($step['field_name'])) {
                    $steps[] = [
                        'message'    => trim($step['message']),
                        'cache_key'  => trim($step['cache_key']),
                        'field_name' => trim($step['field_name']),
                    ];
                }
            }

            if (empty($triggers)) {
                return redirect()->back()->withInput()->with('error', 'At least one trigger is required');
            }

            if (empty($steps)) {
                return redirect()->back()->withInput()->with('error', 'At least one step is required');
            }

            // Process data_config JSON
            $dataConfig = trim($request->data_config ?? '{}');
            if (!empty($dataConfig) && $dataConfig !== '{}') {
                $decoded = json_decode($dataConfig, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return redirect()->back()->withInput()->with('error', 'Invalid JSON in data configuration');
                }
                $dataConfigToStore = $decoded;
            } else {
                $dataConfigToStore = [];
            }

            $flow->update([
                'flow_name'  => $validated['flow_name'],
                'flow_type'  => $validated['flow_type'],
                'triggers'   => $triggers,
                'steps'      => $steps,
                'data_config'=> $dataConfigToStore,
                'priority'   => $validated['priority'] ?? 0,
                'is_active'  => $request->boolean('is_active', true),
            ]);

            return redirect()->route('admin.flows.index', $company->id)
                ->with('success', 'Flow "' . $flow->flow_name . '" updated successfully!');

        } catch (ValidationException $e) {
            return redirect()->back()->withInput()->withErrors($e->errors())->with('error', 'Validation failed.');
        } catch (\Exception $e) {
            Log::error('Flow update error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Error updating flow: ' . $e->getMessage());
        }
    }

    public function destroy(Company $company, ChatbotFlow $flow)
    {
        if ($flow->company_id !== $company->id) {
            abort(404);
        }

        $flow->delete();

        return redirect()->route('admin.flows.index', $company->id)
            ->with('success', 'Flow deleted successfully!');
    }
}
