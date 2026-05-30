<?php

namespace App\Http\Controllers;

use App\Models\ButtonTemplate;
use App\Models\ChatbotConfig;
use App\Models\ChatbotFlow;
use App\Models\Company;
use App\Models\CompanyApiIntegration;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChatbotWizardController extends Controller
{
    /**
     * Load the chatbot wizard page for a specific company.
     * Fetches all related data: configs, flows, buttons, products, services, api integrations.
     */
    public function index(Company $company)
    {
        $chatbotConfigs  = ChatbotConfig::where('company_id', $company->id)->get();
        $flows           = ChatbotFlow::where('company_id', $company->id)->orderBy('priority', 'desc')->get();
        $buttonTemplates = ButtonTemplate::where('company_id', $company->id)->orderBy('priority', 'desc')->get();
        $products        = Product::where('company_id', $company->id)->orderBy('display_order')->get();
        $services        = Service::where('company_id', $company->id)->orderBy('display_order')->get();
        $apiIntegrations = CompanyApiIntegration::where('company_id', $company->id)->get();

        // Load company settings relationship (used for SMTP, AI keys etc.)
        $company->load('settings');

        return view('admin.chatbotwizard.index', compact(
            'company',
            'chatbotConfigs',
            'flows',
            'buttonTemplates',
            'products',
            'services',
            'apiIntegrations'
        ));
    }

    /**
     * Save or update a chatbot config message.
     * Config key is unique per company (e.g. welcome_message, error_message).
     */
    public function saveConfig(Request $request, Company $company)
    {
        $request->validate([
            'config_key'   => 'required|string|max:100',
            'config_value' => 'required|string',
            'category'     => 'nullable|string|max:50',
        ]);

        $configId = $request->input('config_id');

        if ($configId) {
            // Update existing config
            $config = ChatbotConfig::findOrFail($configId);
            $config->update([
                'config_key'   => $request->input('config_key'),
                'config_value' => $request->input('config_value'),
                'category'     => $request->input('category', 'messages'),
            ]);
            $message = 'Config updated successfully!';
        } else {
            // Create new config or update if key already exists for this company
            ChatbotConfig::updateOrCreate(
                ['company_id' => $company->id, 'config_key' => $request->input('config_key')],
                ['config_value' => $request->input('config_value'), 'category' => $request->input('category', 'messages')]
            );
            $message = 'Config created successfully!';
        }

        return redirect()->route('admin.chatbotwizard.index', $company)
            ->with('success', $message)
            ->with('active_tab', $request->input('active_tab', 'configs'));
    }

    /**
     * Delete a chatbot config message.
     */
    public function deleteConfig(Company $company, ChatbotConfig $config)
    {
        $activeTab = request()->input('active_tab', 'configs');
        $config->delete();

        return redirect()->route('admin.chatbotwizard.index', $company)
            ->with('success', 'Config deleted successfully!')
            ->with('active_tab', $activeTab);
    }

    /**
     * Save or update a button template.
     * Buttons are stored as a JSON array in the database.
     */
    public function saveButton(Request $request, Company $company)
    {
        try {
            $request->validate([
                'template_name' => 'required|string|max:255',
                'context'       => 'required|string|max:255',
                'buttons'       => 'required|json',
                'priority'      => 'nullable|integer',
                'is_active'     => 'nullable|boolean',
            ]);

            $buttons = json_decode($request->buttons, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->withErrors(['buttons' => 'Invalid JSON format'])->withInput();
            }

            // Re-encode to ensure clean JSON without slashes or unicode escapes
            $buttonsJson = json_encode($buttons, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $data = [
                'template_name' => $request->template_name,
                'context'       => $request->context,
                'buttons'       => $buttonsJson,
                'priority'      => $request->priority ?? 0,
                'is_active'     => $request->boolean('is_active'),
            ];

            if ($request->filled('button_id')) {
                // Update existing button template
                ButtonTemplate::where('id', $request->button_id)->update($data);
                $message = 'Button template updated successfully!';
            } else {
                // Create new button template
                $data['company_id'] = $company->id;
                ButtonTemplate::create($data);
                $message = 'Button template created successfully!';
            }

            return redirect()->route('admin.chatbotwizard.index', $company)
                ->with('success', $message)
                ->with('active_tab', $request->input('active_tab', 'buttons'));

        } catch (\Throwable $e) {
            Log::error('Button Template Save Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Something went wrong. Please try again.')->withInput();
        }
    }

    /**
     * Delete a button template.
     */
    public function deleteButton(Company $company, ButtonTemplate $template)
    {
        $activeTab = request()->input('active_tab', 'buttons');
        $template->delete();

        return redirect()->route('admin.chatbotwizard.index', $company)
            ->with('success', 'Button template deleted successfully!')
            ->with('active_tab', $activeTab);
    }

    /**
     * Delete a chatbot flow.
     */
    public function deleteFlow(Company $company, ChatbotFlow $flow)
    {
        $activeTab = request()->input('active_tab', 'flows');
        $flow->delete();

        return redirect()->route('admin.chatbotwizard.index', $company)
            ->with('success', 'Flow deleted successfully!')
            ->with('active_tab', $activeTab);
    }

    /**
     * Save or update a product.
     * Keywords are stored as a JSON array.
     */
    public function saveProduct(Request $request, Company $company)
    {
        $request->validate([
            'name'              => 'required|string|max:255',
            'slug'              => 'required|string|max:191',
            'short_description' => 'required|string',
            'full_description'  => 'nullable|string',
            'keywords'          => 'required|string',
            'icon'              => 'nullable|string|max:255',
            'display_order'     => 'nullable|integer',
            'demo_available'    => 'nullable|boolean',
            'is_featured'       => 'nullable|boolean',
            'is_active'         => 'nullable|boolean',
        ]);

        $keywords = json_decode($request->input('keywords'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->withErrors(['keywords' => 'Invalid JSON format for keywords'])->withInput();
        }

        $productId = $request->input('product_id');

        $data = [
            'company_id'        => $company->id,
            'name'              => $request->input('name'),
            'slug'              => $request->input('slug'),
            'short_description' => $request->input('short_description'),
            'full_description'  => $request->input('full_description'),
            'keywords'          => $keywords,
            'icon'              => $request->input('icon'),
            'display_order'     => $request->input('display_order', 0),
            'demo_available'    => $request->has('demo_available') ? 1 : 0,
            'is_featured'       => $request->has('is_featured') ? 1 : 0,
            'is_active'         => $request->has('is_active') ? 1 : 0,
        ];

        if ($productId) {
            // Update existing product
            $product = Product::findOrFail($productId);
            $product->update($data);
            $message = 'Product updated successfully!';
        } else {
            // Create new product
            Product::create($data);
            $message = 'Product created successfully!';
        }

        return redirect()->route('admin.chatbotwizard.index', $company)
            ->with('success', $message)
            ->with('active_tab', $request->input('active_tab', 'products'));
    }

    /**
     * Delete a product.
     */
    public function deleteProduct(Company $company, Product $product)
    {
        $activeTab = request()->input('active_tab', 'products');
        $product->delete();

        return redirect()->route('admin.chatbotwizard.index', $company)
            ->with('success', 'Product deleted successfully!')
            ->with('active_tab', $activeTab);
    }

    /**
     * Save or update a service.
     * Keywords are stored as a JSON array.
     */
    public function saveService(Request $request, Company $company)
    {
        $request->validate([
            'name'              => 'required|string|max:255',
            'slug'              => 'required|string|max:255',
            'short_description' => 'required|string',
            'full_description'  => 'nullable|string',
            'keywords'          => 'required|string',
            'icon'              => 'nullable|string|max:255',
            'display_order'     => 'nullable|integer',
            'enquiry_available' => 'nullable|boolean',
            'is_active'         => 'nullable|boolean',
        ]);

        $keywords = json_decode($request->input('keywords'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->withErrors(['keywords' => 'Invalid JSON format for keywords'])->withInput();
        }

        $serviceId = $request->input('service_id');

        $data = [
            'company_id'        => $company->id,
            'name'              => $request->input('name'),
            'slug'              => $request->input('slug'),
            'short_description' => $request->input('short_description'),
            'full_description'  => $request->input('full_description'),
            'keywords'          => $keywords,
            'icon'              => $request->input('icon'),
            'display_order'     => $request->input('display_order', 0),
            'enquiry_available' => $request->has('enquiry_available') ? 1 : 0,
            'is_active'         => $request->has('is_active') ? 1 : 0,
        ];

        if ($serviceId) {
            // Update existing service
            $service = Service::findOrFail($serviceId);
            $service->update($data);
            $message = 'Service updated successfully!';
        } else {
            // Create new service
            Service::create($data);
            $message = 'Service created successfully!';
        }

        return redirect()->route('admin.chatbotwizard.index', $company)
            ->with('success', $message)
            ->with('active_tab', $request->input('active_tab', 'services'));
    }

    /**
     * Delete a service.
     */
    public function deleteService(Company $company, Service $service)
    {
        $activeTab = request()->input('active_tab', 'services');
        $service->delete();

        return redirect()->route('admin.chatbotwizard.index', $company)
            ->with('success', 'Service deleted successfully!')
            ->with('active_tab', $activeTab);
    }

    /**
     * Save or update an API integration for a company.
     *
     * Each integration stores:
     * - auth_base_url: The base URL of the login/auth microservice
     * - auth_endpoint: The login endpoint path (e.g. /api/auth/login)
     * - token_path: Dot-notation path to extract token from login response (e.g. data.access_token)
     * - auth_identifier_field: Field name sent as header with API calls (e.g. email)
     * - token_ttl: How long the token should be cached in seconds
     * - services: Key-value map of microservice names to their base URLs
     *             (e.g. {"transaction": "https://transaction.milestonetest.online"})
     *
     * These credentials are never hardcoded in flows.
     * Flows reference integrations by key (e.g. "danabook") in their data_config.
     * ChatController looks up this table at runtime to make API calls.
     */
   public function saveApiIntegration(Request $request, Company $company)
{
    $request->validate([
        'integration_key'       => 'required|string|max:50',
        'auth_base_url'         => 'required|url',
        'auth_endpoint'         => 'required|string',
        'token_path'            => 'required|string',
        'auth_identifier_field' => 'nullable|string',
        'token_ttl'             => 'nullable|integer',
        'services_json'         => 'nullable|string',
    ]);

    // Parse services from JSON textarea
    $services = [];
    $rawJson = trim($request->input('services_json', '{}'));
    if (!empty($rawJson) && $rawJson !== '{}') {
        $decoded = json_decode($rawJson, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $services = $decoded;
        } else {
            return back()->withErrors(['services_json' => 'Invalid JSON format'])->withInput();
        }
    }

    $integrationData = [
        'auth_base_url'         => rtrim($request->auth_base_url, '/'),
        'auth_endpoint'         => $request->auth_endpoint,
        'token_path'            => $request->token_path,
        'auth_identifier_field' => $request->auth_identifier_field ?? 'email',
        'token_ttl'             => $request->token_ttl ?? 3600,
        'services'              => $services,
        'is_active'             => true,
    ];

    $integrationId = $request->input('integration_id');

    if ($integrationId) {
        $integration = CompanyApiIntegration::where('company_id', $company->id)
            ->findOrFail($integrationId);
        $integration->update(array_merge(
            ['integration_key' => $request->integration_key],
            $integrationData
        ));
        $message = 'Integration updated successfully!';
    } else {
        CompanyApiIntegration::updateOrCreate(
            ['company_id' => $company->id, 'integration_key' => $request->integration_key],
            $integrationData
        );
        $message = 'Integration saved successfully!';
    }

    return redirect()->route('admin.chatbotwizard.index', $company)
        ->with('success', $message)
        ->with('active_tab', 'integrations');
}

    /**
     * Delete an API integration by its key.
     * The key is a string like "danabook" — not the DB id.
     */
    public function deleteApiIntegration(Company $company, string $key)
    {
        CompanyApiIntegration::where('company_id', $company->id)
            ->where('integration_key', $key)
            ->delete();

        return redirect()->route('admin.chatbotwizard.index', $company)
            ->with('success', 'Integration removed!')
            ->with('active_tab', 'integrations');
    }
}