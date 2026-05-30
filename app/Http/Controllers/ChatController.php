<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\ButtonTemplate;
use App\Models\ChatbotFlow;
use App\Models\CompanyApiIntegration;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    /** @var Company Current company resolved from slug or default */
    private $company;

    // =========================================================
    // CHAT VIEW
    // =========================================================

    /**
     * Render the chat blade view for a given company slug.
     * Falls back to first active company if slug not found.
     */
    public function showChat($companySlug)
    {
        $company = Company::where('slug', $companySlug)
            ->where('is_active', true)
            ->first();

        if (!$company) {
            $company = Company::where('is_active', true)->first();
        }

        return view('chat', ['company' => $company]);
    }

    // =========================================================
    // MAIN ENTRY POINT
    // =========================================================

    /**
     * Single entry point for all incoming requests.
     * Handles CORS preflight, then routes to web chat or Telegram.
     */
    public function handle(Request $request)
    {
        // Handle CORS preflight
        if ($request->getMethod() === 'OPTIONS') {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', '*');
        }

        try {
            // Route: web chat vs Telegram webhook
            if ($request->expectsJson() && $request->has('message') && $request->has('session_id')) {
                $response = $this->handleWebChat($request);
            } else {
                $response = $this->handleTelegram($request);
            }

            return $response
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', '*');
        } catch (\Throwable $e) {
            Log::error('Handler Error', ['error' => $e->getMessage()]);
            return response()->json(['ok' => false])
                ->header('Access-Control-Allow-Origin', '*');
        }
    }

    // =========================================================
    // CHANNEL HANDLERS
    // =========================================================

    /**
     * Handle messages from web chat widget.
     * Resolves company from URL slug, then processes message.
     */
    public function handleWebChat(Request $request)
    {
        try {
            // Extract company slug from URL path (first segment)
            $path        = $request->path();
            $segments    = explode('/', $path);
            $companySlug = $segments[0] ?? 'milestone-it';

            $this->company = Company::where('slug', $companySlug)
                ->where('is_active', true)
                ->with(['chatbotConfigs', 'buttonTemplates', 'chatbotFlows'])
                ->first();

            if (!$this->company) {
                $this->company = Company::where('is_active', true)->first();
            }

            Log::info('Web Chat Request:', [
                'company'    => $this->company->name,
                'message'    => $request->input('message'),
                'session_id' => $request->input('session_id'),
            ]);

            $message   = trim($request->input('message', ''));
            $sessionId = $request->input('session_id');

            if (!$message || !$sessionId) {
                return response()->json(['reply' => '❌ Invalid request']);
            }

            return $this->processMessage($sessionId, $message);
        } catch (\Throwable $e) {
            Log::error('Web Chat Error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['reply' => '❌ Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Handle messages from Telegram webhook.
     * Supports both regular messages and inline keyboard callbacks.
     */
    private function handleTelegram(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        Log::info('Telegram RAW:', $data);

        $this->company = Company::where('is_active', true)
            ->with(['chatbotConfigs', 'buttonTemplates', 'chatbotFlows'])
            ->firstOrFail();

        // Inline button callback
        if (isset($data['callback_query'])) {
            $chatId = $data['callback_query']['message']['chat']['id'];
            $msg    = strtolower(trim($data['callback_query']['data']));
            $this->answerCallbackQuery($data['callback_query']['id']);
            return $this->processMessage($chatId, $msg);
        }

        if (!isset($data['message'])) {
            return response()->json(['ok' => true]);
        }

        $chatId = $data['message']['chat']['id'] ?? null;
        $msg    = trim($data['message']['text'] ?? '');

        if (!$chatId) {
            return response()->json(['ok' => false]);
        }

        return $this->processMessage($chatId, $msg);
    }

    // =========================================================
    // CORE MESSAGE PROCESSOR
    // =========================================================

    /**
     * Central message routing logic with 5 priority levels:
     *  1. Global commands (hi, menu, back, exit)
     *  2. Continue an active flow
     *  3. Detect and start a new flow
     *  4. Direct display commands (products, services, contact)
     *  5. AI fallback
     */
    private function processMessage($chatId, $msg)
    {
        $originalMsg = $msg;
        $msgLower    = strtolower(trim($msg));

        // Check if user is currently inside a flow
        $stepKey     = "step_{$this->company->id}_{$chatId}";
        $currentStep = Cache::get($stepKey);

        Log::info('📨 Process Message:', [
            'company'   => $this->company->name,
            'chatId'    => $chatId,
            'original'  => $originalMsg,
            'lowercase' => $msgLower,
            'in_flow'   => $currentStep ? 'YES' : 'NO',
        ]);

        // --- PRIORITY 1: Global Commands ---
        if (in_array($msgLower, ['hi', 'hello', 'hai', 'hey', '/start', 'start'])) {
            $this->clearAllData($chatId);
            return $this->showMainMenu($chatId);
        }

        if ($msgLower === 'menu') {
            $this->clearAllData($chatId);
            return $this->showMainMenu($chatId);
        }

        if ($msgLower === 'back') {
            $this->clearAllData($chatId);
            return $this->showMainMenu($chatId);
        }

        if ($msgLower === 'exit') {
            $this->clearAllData($chatId);
            $goodbye = $this->company->getConfig('goodbye_message', 'Thank you! 👋');
            return $this->sendMessage($chatId, $goodbye);
        }
        if ($msgLower === 'login_email') {
            $authFlow = ChatbotFlow::where('company_id', $this->company->id)
                ->where('flow_name', 'email_password_login') // ✅ exact flow
                ->where('is_active', true)
                ->first();

            if ($authFlow) {
                return $this->startFlow($chatId, $authFlow);
            }
        }

        if ($msgLower === 'login_mobile') {
            $mobileFlow = ChatbotFlow::where('company_id', $this->company->id)
                ->where('flow_name', 'mobile_otp_login')
                ->where('is_active', true)
                ->first();

            if ($mobileFlow) {
                return $this->startFlow($chatId, $mobileFlow);
            }

            return $this->sendMessage(
                $chatId,
                "📱 Mobile OTP login is not configured yet.\n\nPlease use 📧 Email & Password.",
                $this->getButtons('login_methods')
            );
        }

        if ($msgLower === 'login_google') {
            return $this->handleGoogleLogin($chatId);
        }

        // --- PRIORITY 2: Continue Active Flow ---
        if ($currentStep) {
            Log::info('⚡ Continuing Flow:', ['flow' => $currentStep['flow_name']]);
            return $this->continueFlow($chatId, $originalMsg, $currentStep);
        }

        // --- PRIORITY 3: Detect New Flow ---
        $flow = $this->detectFlow($msgLower);
        if ($flow) {
            Log::info('🎯 Flow Detected:', [
                'flow_name' => $flow->flow_name,
                'flow_type' => $flow->flow_type,
            ]);
            return $this->startFlow($chatId, $flow);
        }

        // --- PRIORITY 4: Direct Display Commands ---
        if ($this->matchesKeyword($msgLower, ['product', 'products', 'show products', 'our products'])) {
            return $this->showProducts($chatId);
        }

        if ($this->matchesKeyword($msgLower, ['service', 'services', 'show services', 'our services'])) {
            return $this->showServices($chatId);
        }

        if ($this->matchesKeyword($msgLower, ['contact', 'phone', 'call', 'email', 'how can i contact'])) {
            return $this->showContactInfo($chatId);
        }

        // --- PRIORITY 5: AI Fallback ---
        Log::info('🤖 Using AI Fallback');
        return $this->handleWithAI($chatId, $originalMsg);
    }

    /**
     * Check if text contains any of the given keywords (case-insensitive).
     */
    private function matchesKeyword($text, $keywords)
    {
        foreach ($keywords as $keyword) {
            if (stripos($text, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    // =========================================================
    // FLOW ENGINE
    // =========================================================

    /**
     * Scan active flows for a trigger that matches the user message.
     * Checks exact match first, then substring match.
     * Flows are ordered by priority (highest first).
     */
    private function detectFlow($message)
    {
        $flows = ChatbotFlow::where('company_id', $this->company->id)
            ->where('is_active', true)
            ->orderBy('priority', 'desc')
            ->get();

        Log::info('🔍 Detecting Flow:', [
            'message'     => $message,
            'total_flows' => $flows->count(),
        ]);

        foreach ($flows as $flow) {
            $triggers = $flow->triggers ?? [];

            foreach ($triggers as $trigger) {
                $triggerLower = strtolower(trim($trigger));

                // Exact match
                if ($message === $triggerLower) {
                    Log::info('✅ EXACT MATCH:', ['flow' => $flow->flow_name, 'trigger' => $trigger]);
                    return $flow;
                }

                // Substring match
                if (stripos($message, $triggerLower) !== false) {
                    Log::info('✅ CONTAINS MATCH:', ['flow' => $flow->flow_name, 'trigger' => $trigger]);
                    return $flow;
                }
            }
        }

        Log::info('❌ No Flow Matched');
        return null;
    }

    /**
     * Begin a flow by saving state to cache and sending the first step message.
     * quick_reply flows send a single message immediately without saving state.
     */
    private function startFlow($chatId, $flow)
    {
        Log::info('🚀 Starting Flow:', [
            'flow_name' => $flow->flow_name,
            'flow_type' => $flow->flow_type,
        ]);

        $steps = $flow->steps;

        if (empty($steps)) {
            Log::error('❌ No steps in flow:', ['flow' => $flow->flow_name]);
            return $this->showMainMenu($chatId);
        }


        // quick_reply: decode data_config and check for login_selector type
        if ($flow->flow_type === 'quick_reply') {
            $dataConfig = is_string($flow->data_config)
                ? (json_decode($flow->data_config, true) ?? [])
                : ($flow->data_config ?? []);

            // Login selector — show method buttons instead of going to main menu
            if (($dataConfig['type'] ?? '') === 'login_selector') {
                $message = $steps[0]['message'] ?? '🔐 Please choose your login method:';
                $this->clearAllData($chatId);

                // Store pending service so we know what to call after login
                if (!empty($dataConfig['after_auth_service'])) {
                    Cache::put(
                        "pending_service_{$this->company->id}_{$chatId}",
                        [
                            'integration_key'    => $dataConfig['integration_key'],
                            'after_auth_service' => $dataConfig['after_auth_service'],
                            'success_buttons'    => $dataConfig['success_buttons'] ?? 'default',
                        ],
                        3600
                    );
                }

                // Check if already logged in — skip selector, call service directly
                $integrationKey = $dataConfig['integration_key'] ?? null;
                if ($integrationKey) {
                    $tokenCacheKey = "api_token_{$this->company->id}_{$chatId}_{$integrationKey}";
                    if (Cache::get($tokenCacheKey)) {
                        Log::info('✅ Token valid — skipping login selector, calling service directly');
                        Cache::forget("pending_service_{$this->company->id}_{$chatId}");
                        return $this->executeApiServiceAndRespond(
                            $chatId,
                            $integrationKey,
                            $dataConfig['after_auth_service'],
                            $dataConfig
                        );
                    }
                }

                return $this->sendMessage($chatId, $message, $this->getButtons('login_methods'));
            }

            // Default quick_reply — just show message
            $message = $steps[0]['message'] ?? 'Here is your information:';
            $this->clearAllData($chatId);
            return $this->sendMessage($chatId, $message, $this->getButtons('default'));
        }

        // All other types: save flow state to cache, show first step
        Cache::put("step_{$this->company->id}_{$chatId}", [
            'flow_id'     => $flow->id,
            'flow_name'   => $flow->flow_name,
            'flow_type'   => $flow->flow_type,
            'step_index'  => 0,
            'data_config' => $flow->data_config,
        ], 3600);

        Log::info('💾 Flow State Saved:', [
            'step_index' => 0,
            'message'    => $steps[0]['message'],
        ]);

        return $this->sendMessage($chatId, $steps[0]['message'], $this->getButtons('navigation'));
    }

    /**
     * Process user input for the current step of an active flow.
     * Saves the input, advances to next step, or completes the flow.
     */
    private function continueFlow($chatId, $userInput, $stepState)
    {
        Log::info('⏭️ Continue Flow:', [
            'flow'         => $stepState['flow_name'],
            'current_step' => $stepState['step_index'],
            'user_input'   => $userInput,
        ]);

        // Allow user to interrupt the flow with global commands
        if (in_array(strtolower($userInput), ['menu', 'exit', 'back'])) {
            Log::info('🛑 Flow Interrupted by User');
            $this->clearAllData($chatId);
            return $this->processMessage($chatId, $userInput);
        }

        $flow = ChatbotFlow::find($stepState['flow_id']);

        if (!$flow) {
            Log::error('❌ Flow not found:', ['flow_id' => $stepState['flow_id']]);
            return $this->showMainMenu($chatId);
        }

        $steps            = $flow->steps;
        $currentStepIndex = $stepState['step_index'];
        $currentStepData  = $steps[$currentStepIndex];

        // Save input under cache_key (for per-step lookup) and field_name (for grouped lookup)
        $cacheKey  = $currentStepData['cache_key']  ?? "step_{$currentStepIndex}";
        $fieldName = $currentStepData['field_name'] ?? $cacheKey;

        Cache::put("{$cacheKey}_{$this->company->id}_{$chatId}", $userInput, 3600);

        // Also store in flat field map for form/auth completion
        $fieldsKey          = "flow_fields_{$this->company->id}_{$chatId}";
        $fields             = Cache::get($fieldsKey, []);
        $fields[$fieldName] = $userInput;
        Cache::put($fieldsKey, $fields, 3600);

        Log::info('💾 Field Saved:', ['field_name' => $fieldName, 'value' => $userInput]);

        // If last step — complete the flow
        if ($currentStepIndex >= count($steps) - 1) {
            Log::info('✅ Last Step Reached - Completing Flow');
            return $this->completeFlow($chatId, $flow, $stepState);
        }

        // Move to next step
        $nextStepIndex           = $currentStepIndex + 1;
        $stepState['step_index'] = $nextStepIndex;
        Cache::put("step_{$this->company->id}_{$chatId}", $stepState, 3600);

        Log::info('➡️ Moving to Next Step:', [
            'next_step' => $nextStepIndex,
            'message'   => $steps[$nextStepIndex]['message'],
        ]);

        return $this->sendMessage($chatId, $steps[$nextStepIndex]['message'], $this->getButtons('navigation'));
    }

    /**
     * Route to the correct completion handler based on flow type.
     */
    private function completeFlow($chatId, $flow, $stepState)
    {
        $flowType   = $flow->flow_type;
        $dataConfig = $stepState['data_config'] ?? $flow->data_config;

        Log::info('🏁 Completing Flow:', ['flow' => $flow->flow_name, 'type' => $flowType]);

        if ($flowType === 'data_query') {
            return $this->executeDataQuery($chatId, $flow, $dataConfig);
        }

        if ($flowType === 'form_collection') {
            return $this->executeFormSave($chatId, $flow, $dataConfig);
        }

        if ($flowType === 'quick_reply') {
            $this->clearAllData($chatId);
            return $this->showMainMenu($chatId);
        }

        if ($flowType === 'api_auth') {
            return $this->executeApiAuth($chatId, $flow, $dataConfig);
        }

        // Default fallback
        $this->clearAllData($chatId);
        return $this->showMainMenu($chatId);
    }

    // =========================================================
    // FLOW COMPLETION HANDLERS
    // =========================================================

    /**
     * data_query flow: search local DB table with user-provided value
     * and return a formatted response using response_template.
     */
    private function executeDataQuery($chatId, $flow, $config)
    {
        $steps    = $flow->steps;
        $lastStep = end($steps);

        $searchKey   = $lastStep['cache_key'];
        $searchValue = Cache::get("{$searchKey}_{$this->company->id}_{$chatId}");

        if (!$searchValue) {
            Log::error('No search value found');
            return $this->showMainMenu($chatId);
        }

        try {
            $table         = $config['table'];
            $searchColumns = $config['search_columns'] ?? [];
            $query         = DB::table($table);

            foreach ($searchColumns as $column) {
                $query->orWhere($column, $searchValue);
            }

            $result = $query->first();

            if ($result) {
                $template      = $config['response_template'] ?? 'Result found';
                $response      = $this->formatTemplate($template, (array) $result);
                $buttonContext = $config['success_buttons'] ?? 'default';
            } else {
                $response      = $config['not_found_message'] ?? 'No results found';
                $buttonContext = $config['not_found_buttons'] ?? 'default';
            }

            $this->clearAllData($chatId);
            return $this->sendMessage($chatId, $response, $this->getButtons($buttonContext));
        } catch (\Exception $e) {
            Log::error('Data Query Error:', ['error' => $e->getMessage()]);
            $errorMsg = $this->company->getConfig('error_message', 'Something went wrong');
            return $this->sendMessage($chatId, $errorMsg, $this->getButtons('default'));
        }
    }

    /**
     * form_collection flow: insert collected fields into DB table,
     * optionally send email notification.
     */
    private function executeFormSave($chatId, $flow, $config)
    {
        $fieldsKey = "flow_fields_{$this->company->id}_{$chatId}";
        $fields    = Cache::get($fieldsKey, []);

        Log::info('💾 Form Data to Save:', $fields);

        if (empty($fields)) {
            Log::error('No form data found');
            return $this->showMainMenu($chatId);
        }

        try {
            $fields['created_at'] = now();
            $fields['updated_at'] = now();

            $table = $config['save_to_table'];
            DB::table($table)->insert($fields);

            Log::info('✅ Form Saved:', ['table' => $table, 'data' => $fields]);

            // Optional email notification
            if (isset($config['send_email']) && $config['send_email']) {
                $channel = request()->expectsJson() ? 'web' : 'telegram';
                $this->sendEmailNotification($flow->flow_name, $fields, $chatId, $channel);
            }

            $response      = $config['success_message'] ?? 'Successfully submitted!';
            $buttonContext = $config['success_buttons']  ?? 'default';

            $this->clearAllData($chatId);
            return $this->sendMessage($chatId, $response, $this->getButtons($buttonContext));
        } catch (\Exception $e) {
            Log::error('Form Save Error:', ['error' => $e->getMessage()]);
            $errorMsg = $this->company->getConfig('error_message', 'Something went wrong');
            return $this->sendMessage($chatId, $errorMsg, $this->getButtons('default'));
        }
    }

    /**
     * api_auth flow: authenticate user against external API,
     * cache the token, then optionally auto-call an after_auth_service.
     *
     * data_config example:
     * {
     *   "integration_key": "danabook",
     *   "after_auth_service": "cash_balance",
     *   "success_buttons": "default"
     * }
     */
    private function executeApiAuth($chatId, $flow, $config)
    {
        $fieldsKey      = "flow_fields_{$this->company->id}_{$chatId}";
        $fields         = Cache::get($fieldsKey, []);
        $integrationKey = $config['integration_key'];
        $authType       = $config['auth_type'] ?? 'email_password'; // NEW LINE

        // ── Mobile OTP branch ───────────────────────────────────────────
        if ($authType === 'mobile_otp') {
            $mobile = $fields['mobile'] ?? null;
            $otp    = $fields['otp']    ?? null;

            if (!$mobile || !$otp) {
                return $this->sendMessage(
                    $chatId,
                    '❌ Mobile or OTP missing. Please try again.',
                    $this->getButtons('login_methods')
                );
            }

            $token = $this->authenticateMobileOtp($chatId, $integrationKey, $mobile, $otp);

            if (!$token) {
                $this->clearAllData($chatId);
                return $this->sendMessage(
                    $chatId,
                    '❌ OTP verification failed. Please try again.',
                    $this->getButtons('login_methods')
                );
            }

            Cache::put("auth_user_{$this->company->id}_{$chatId}", $mobile, 3600);
            $this->clearAllData($chatId);

            if (!empty($config['after_auth_service'])) {
                return $this->executeApiServiceAndRespond(
                    $chatId,
                    $integrationKey,
                    $config['after_auth_service'],
                    $config
                );
            }

            return $this->sendMessage(
                $chatId,
                '✅ Mobile login successful!',
                $this->getButtons($config['success_buttons'] ?? 'default')
            );
        }

        // ── Email & Password branch (existing code unchanged below) ─────
        $email     = $fields['email']    ?? null;
        $password  = $fields['password'] ?? null;
        $fieldsKey = "flow_fields_{$this->company->id}_{$chatId}";
        $fields    = Cache::get($fieldsKey, []);

        $email    = $fields['email']    ?? null;
        $password = $fields['password'] ?? null;

        if (!$email || !$password) {
            return $this->sendMessage(
                $chatId,
                '❌ Email or password missing. Please try again.',
                $this->getButtons('default')
            );
        }

        $integrationKey = $config['integration_key'];

        // Resolve IP (support proxy headers)
        $ipAddress = $fields['ip_address']
            ?? $config['ip_address']
            ?? request()->ip();

        Log::info('🔐 Attempting API Auth:', [
            'integration' => $integrationKey,
            'email'       => $email,
            'ip_address'  => $ipAddress,
        ]);

        $token = $this->authenticateApi($chatId, $integrationKey, $email, $password, null, $ipAddress);

        if (!$token) {
            $this->clearAllData($chatId);
            return $this->sendMessage(
                $chatId,
                '❌ Login failed. Invalid email or password. Please try again.',
                $this->getButtons('default')
            );
        }

        // Cache email for use as identifier header in subsequent service calls
        Cache::put("auth_user_{$this->company->id}_{$chatId}", $email, 3600);

        // Clear flow state (token and auth_user are kept separately)
        $this->clearAllData($chatId);

        // If configured, automatically call a service after login
        if (!empty($config['after_auth_service'])) {
            Log::info('📡 Auto-calling service after auth:', ['service' => $config['after_auth_service']]);
            return $this->executeApiServiceAndRespond(
                $chatId,
                $integrationKey,
                $config['after_auth_service'],
                $config
            );
        }

        $successMsg    = $config['success_message'] ?? "✅ Login successful!\n\nWhat would you like to do?";
        $buttonContext = $config['success_buttons']  ?? 'logged_in';

        return $this->sendMessage($chatId, $successMsg, $this->getButtons($buttonContext));
    }

    /**
     * Call an external API service using the cached Bearer token.
     *
     * Sends:
     *   Authorization: Bearer <token>
     *   identifier: <email>        (as custom header)
     *   Body: service.body + userid (injected from cache)
     *
     * Handles both flat and array (list) API responses.
     *
     * services DB JSON example:
     * {
     *   "cash_balance": {
     *     "base_url": "https://dashboardhub.zerobook.shop",
     *     "endpoint": "/api/v1/dashboardvalue",
     *     "method": "post",
     *     "body": { "api": "/api/v1/dashboardvalues", "apiargument": "cashbalance" },
     *     "response_template": "💰 Your Cash Balance:\n\n{data_list}",
     *     "list_field": "data",
     *     "list_template": "• {name}: {value}"
     *   }
     * }
     */
    private function executeApiServiceAndRespond($chatId, $integrationKey, $serviceKey, $config)
    {
        $integration = CompanyApiIntegration::where('company_id', $this->company->id)
            ->where('integration_key', $integrationKey)
            ->where('is_active', 1)
            ->first();

        if (!$integration) {
            return $this->sendMessage($chatId, '❌ Integration not found.', $this->getButtons('default'));
        }

        // Retrieve cached token
        $token = $this->getApiToken($chatId, $integrationKey);

        if (!$token) {
            return $this->sendMessage($chatId, '❌ Session expired. Please login again.', $this->getButtons('default'));
        }

        // Parse services JSON from DB
        $services = is_string($integration->services)
            ? json_decode($integration->services, true)
            : $integration->services;

        if (!isset($services[$serviceKey])) {
            Log::error('❌ Service key not found:', [
                'key'       => $serviceKey,
                'available' => array_keys($services ?? []),
            ]);
            return $this->sendMessage($chatId, '❌ Service not configured.', $this->getButtons('default'));
        }

        $service = $services[$serviceKey];

        // Use service-level base_url if provided, else fall back to integration base_url
        $baseUrl = isset($service['base_url'])
            ? rtrim($service['base_url'], '/')
            : rtrim($integration->auth_base_url, '/');

        $url    = $baseUrl . '/' . ltrim($service['endpoint'], '/');
        $method = strtolower($service['method'] ?? 'get');

        try {
            // Retrieve cached email and userid
            $email  = Cache::get("auth_user_{$this->company->id}_{$chatId}");
            $userId = Cache::get("auth_userid_{$this->company->id}_{$chatId}");

            // Build request body from DB config, inject userid dynamically
            $body = $service['body'] ?? [];
            if ($userId) {
                $body['userid'] = (int) $userId;
            }

            Log::info('📦 Service Request:', [
                'url'    => $url,
                'method' => $method,
                'body'   => $body,
                'email'  => $email,
                'userid' => $userId,
                'token'  => substr($token, 0, 20) . '...',
            ]);

            // Send request: Bearer token + identifier header + JSON body
            $response = Http::timeout(15)
                ->withToken($token)               // Authorization: Bearer <token>
                ->withHeaders([
                    'identifier'   => $email ?? '', // custom identifier header
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($url, $body);

            Log::info('📥 Service Response:', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            if (!$response->successful()) {
                Log::error('❌ Service Failed:', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return $this->sendMessage(
                    $chatId,
                    '❌ Could not fetch data. Please try again later.',
                    $this->getButtons('default')
                );
            }

            $data = $response->json();

            // ── Format response ───────────────────────────────────────────
            $template     = $service['response_template'] ?? 'Here is your data.';
            $listField    = $service['list_field']    ?? null;   // e.g. "data"
            $listTemplate = $service['list_template'] ?? null;   // e.g. "• {name}: {value}"

            if ($listField && $listTemplate && isset($data[$listField]) && is_array($data[$listField])) {
                // Array response — build a list string from each item
                $lines = [];
                foreach ($data[$listField] as $item) {
                    $lines[] = $this->formatTemplate($listTemplate, $item);
                }
                $dataList = implode("\n", $lines);

                // Replace {data_list} placeholder in the main template
                $message = str_replace('{data_list}', $dataList, $template);
            } else {
                // Flat response — flatten and replace all {field} placeholders
                $flatData = $this->flattenArray($data);
                $message  = $this->formatTemplate($template, $flatData);
            }

            $buttonContext = $config['success_buttons'] ?? 'default';
            return $this->sendMessage($chatId, $message, $this->getButtons($buttonContext));
        } catch (\Exception $e) {
            Log::error('❌ Service Call Error:', ['error' => $e->getMessage()]);
            return $this->sendMessage($chatId, '❌ Something went wrong. Please try again.', $this->getButtons('default'));
        }
    }
    private function authenticateMobileOtp($chatId, $integrationKey, $mobile, $otp)
    {
        $integration = CompanyApiIntegration::where('company_id', $this->company->id)
            ->where('integration_key', $integrationKey)
            ->where('is_active', 1)
            ->first();

        if (!$integration) {
            Log::error('❌ Integration not found for OTP auth:', ['key' => $integrationKey]);
            return null;
        }

        // extra_auth_fields-ൽ നിന്ന് mobile_otp_endpoint resolve ചെയ്യുക
        $extraFields = is_string($integration->extra_auth_fields)
            ? json_decode($integration->extra_auth_fields, true)
            : ($integration->extra_auth_fields ?? []);

        $otpEndpoint = $extraFields['mobile_otp_endpoint'] ?? '/api/v1/auth/verify-otp';
        $url         = rtrim($integration->auth_base_url, '/') . '/' . ltrim($otpEndpoint, '/');

        try {
            $response = Http::timeout(15)->post($url, [
                'mobile' => $mobile,
                'otp'    => $otp,
                'orgid'  => (string) ($integration->orgid ?? ''),
            ]);

            Log::info('📱 OTP Auth Response:', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            if (!$response->successful()) {
                Log::error('❌ OTP Auth Failed:', ['status' => $response->status()]);
                return null;
            }

            $data  = $response->json();
            $token = $this->extractNestedValue($data, $integration->token_path);

            if (!$token) {
                Log::error('❌ Token not found in OTP response');
                return null;
            }

            // Token cache (email auth-ന്റേതു പോലെ)
            $tokenKey = "api_token_{$this->company->id}_{$chatId}_{$integrationKey}";
            Cache::put($tokenKey, $token, $integration->token_ttl);

            // UserId cache
            $userId = $data['userid'] ?? null;
            if ($userId) {
                Cache::put("auth_userid_{$this->company->id}_{$chatId}", $userId, 3600);
            }

            Log::info('✅ OTP Auth Success');
            return $token;
        } catch (\Exception $e) {
            Log::error('❌ OTP Auth Exception:', ['error' => $e->getMessage()]);
            return null;
        }
    }
    private function handleGoogleLogin($chatId)
    {
        $googleClientId = config('services.google.client_id');

        if (!$googleClientId) {
            return $this->sendMessage(
                $chatId,
                "🔵 Google Login is not set up yet.\n\nPlease use 📧 Email & Password for now.",
                $this->getButtons('login_methods')
            );
        }

        $state       = base64_encode(json_encode([
            'chat_id'    => $chatId,
            'company_id' => $this->company->id,
            'slug'       => $this->company->slug,
        ]));

        $redirectUri = config('app.url') . '/auth/google/callback';

        $googleUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id'     => $googleClientId,
            'redirect_uri'  => $redirectUri,
            'response_type' => 'code',
            'scope'         => 'email profile',
            'state'         => $state,
            'prompt'        => 'select_account',
        ]);

        // Web chat: frontend-ൽ auth_type: google_oauth catch ചെയ്ത് popup open ചെയ്യണം
        if (request()->expectsJson()) {
            return response()->json([
                'reply'      => '🔵 Please login with your Google account:',
                'buttons'    => $this->getButtons('login_methods'),
                'auth_type'  => 'google_oauth',
                'google_url' => $googleUrl,
            ]);
        }

        // Telegram
        return $this->sendMessage(
            $chatId,
            "🔵 *Google Login*\n\n[👆 Click here to login with Google]({$googleUrl})\n\n_Link valid for 10 minutes_",
            $this->getButtons('login_methods')
        );
    }
    // =========================================================
    // EXTERNAL API AUTH
    // =========================================================

    /**
     * POST login credentials to external API and cache the returned token.
     *
     * Sends:
     *   email (or configured identifier field)
     *   password (optionally hashed via password_algo + password_salt)
     *   device_id, ip_address, fingerprint, devicename, orgid, encrypt
     *   + any extra_auth_fields from DB
     *
     * Caches:
     *   api_token_{company}_{chat}_{key}  → Bearer token
     *   auth_userid_{company}_{chat}      → userid from response (if present)
     */
    private function authenticateApi(
        $chatId,
        $integrationKey,
        $identifier,
        $password,
        $deviceId  = null,
        $ipAddress = null
    ) {
        $integration = CompanyApiIntegration::where('company_id', $this->company->id)
            ->where('integration_key', $integrationKey)
            ->where('is_active', 1)
            ->first();

        if (!$integration) {
            Log::error('❌ Integration not found:', ['key' => $integrationKey]);
            return null;
        }

        // Resolve real client IP (support reverse-proxy headers)
        $ipAddress = $ipAddress
            ?? request()->header('X-Forwarded-For')
            ?? request()->header('X-Real-IP')
            ?? request()->ip();

        // Take first IP if comma-separated list
        if (str_contains($ipAddress, ',')) {
            $ipAddress = trim(explode(',', $ipAddress)[0]);
        }

        $url = rtrim($integration->auth_base_url, '/') . '/' . ltrim($integration->auth_endpoint, '/');

        try {
            // ── Optional password hashing ─────────────────────────────────
            $passwordToSend = $password;
            if (!empty($integration->password_algo)) {
                $algo           = strtolower($integration->password_algo);
                $salt           = $integration->password_salt ?? null;
                $passwordInput  = $salt ? ($password . $salt) : $password;
                $passwordToSend = hash($algo, $passwordInput);

                Log::info('🔒 Password hashed:', [
                    'algo'      => $algo,
                    'salt_used' => $salt ? 'yes' : 'no',
                ]);
            }

            // ── Device fingerprint generation ─────────────────────────────
            $userAgent   = request()->header('User-Agent') ?? 'Chatbot-Server';
            $fingerprint = $this->generateFingerprint($userAgent, $ipAddress, $identifier);
            $deviceName  = $this->getDeviceName($userAgent);

            // Unique device ID per session (mimics tab login)
            $deviceId = 'tab-' . time() . '-' . substr(md5($identifier . $ipAddress), 0, 9);

            // ── Build login payload ───────────────────────────────────────
            $body = [
                $integration->auth_identifier_field => $identifier,
                'password'    => $passwordToSend,
                'device_id'   => $deviceId,
                'ip_address'  => $ipAddress,
                'fingerprint' => $fingerprint,
                'devicename'  => $deviceName,
                'orgid'       => (string) ($integration->orgid ?? ''),
                'encrypt'     => true,
            ];

            // ── Merge extra_auth_fields from DB ───────────────────────────
            $extraFields = $integration->extra_auth_fields;
            if (is_string($extraFields)) {
                $extraFields = json_decode($extraFields, true);
            }
            if (!empty($extraFields) && is_array($extraFields)) {
                $body = array_merge($body, $extraFields);
                Log::info('📦 Extra auth fields merged:', array_keys($extraFields));
            }

            Log::info('🔐 Auth API Call:', [
                'url'         => $url,
                'integration' => $integrationKey,
                'email'       => $identifier,
                'device_id'   => $deviceId,
                'ip_address'  => $ipAddress,
                'devicename'  => $deviceName,
                'orgid'       => $body['orgid'],
                'fingerprint' => $fingerprint,
            ]);

            $response = Http::timeout(15)->post($url, $body);

            if (!$response->successful()) {
                Log::error('❌ Auth Failed:', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return null;
            }

            $responseData = $response->json();

            // Extract token using dot-notation path (e.g. "data.access_token")
            $token = $this->extractNestedValue($responseData, $integration->token_path);

            if (!$token) {
                Log::error('❌ Token not found in response:', [
                    'path'     => $integration->token_path,
                    'response' => $responseData,
                ]);
                return null;
            }

            // Cache token
            $tokenCacheKey = "api_token_{$this->company->id}_{$chatId}_{$integrationKey}";
            Cache::put($tokenCacheKey, $token, $integration->token_ttl);

            // Cache userid if returned (needed for subsequent API calls)
            $userId = $responseData['userid'] ?? null;
            if ($userId) {
                Cache::put("auth_userid_{$this->company->id}_{$chatId}", $userId, 3600);
                Log::info('✅ UserID cached:', ['userid' => $userId]);
            }

            Log::info('✅ Token Saved:', [
                'integration' => $integrationKey,
                'ttl'         => $integration->token_ttl,
            ]);

            return $token;
        } catch (\Exception $e) {
            Log::error('❌ Auth Exception:', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Retrieve cached Bearer token for an integration.
     * Returns null if expired or not found.
     */
    private function getApiToken($chatId, $integrationKey)
    {
        $tokenCacheKey = "api_token_{$this->company->id}_{$chatId}_{$integrationKey}";
        $cached        = Cache::get($tokenCacheKey);

        if ($cached) {
            Log::info('✅ Token from cache:', ['integration' => $integrationKey]);
            return $cached;
        }

        return null;
    }

    /**
     * Generic token-based API caller (used outside of flow context).
     * Automatically clears token on 401 Unauthorized.
     */
    public function callApiWithToken($chatId, $integrationKey, $serviceKey, $params = [])
    {
        $integration = CompanyApiIntegration::where('company_id', $this->company->id)
            ->where('integration_key', $integrationKey)
            ->where('is_active', 1)
            ->first();

        if (!$integration) return null;

        $token = $this->getApiToken($chatId, $integrationKey);

        if (!$token) {
            Log::error('❌ No token available for API call');
            return null;
        }

        $services = is_string($integration->services)
            ? json_decode($integration->services, true)
            : $integration->services;

        if (!isset($services[$serviceKey])) {
            Log::error('❌ Service not found:', ['key' => $serviceKey]);
            return null;
        }

        $service = $services[$serviceKey];

        // Respect service-level base_url override
        $baseUrl = isset($service['base_url'])
            ? rtrim($service['base_url'], '/')
            : rtrim($integration->auth_base_url, '/');

        $url    = $baseUrl . '/' . ltrim($service['endpoint'], '/');
        $method = strtolower($service['method'] ?? 'get');

        Log::info('📡 API Call with Token:', ['url' => $url, 'service' => $serviceKey]);

        try {
            $response = Http::timeout(15)
                ->withToken($token)
                ->{$method}($url, $params);

            if ($response->successful()) {
                return $response->json();
            }

            // Auto-invalidate expired token
            if ($response->status() === 401) {
                $tokenCacheKey = "api_token_{$this->company->id}_{$chatId}_{$integrationKey}";
                Cache::forget($tokenCacheKey);
                Log::warning('⚠️ Token expired, cleared cache');
            }

            return null;
        } catch (\Exception $e) {
            Log::error('❌ API Call Exception:', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // =========================================================
    // TEMPLATE & DATA HELPERS
    // =========================================================

    /**
     * Replace {key} placeholders in a template string with values from $data array.
     */
    private function formatTemplate($template, $data)
    {
        foreach ($data as $key => $value) {
            if (!is_array($value)) {
                $template = str_replace("{{$key}}", $value, $template);
            }
        }
        return $template;
    }

    /**
     * Flatten a nested array into dot-notation and shorthand keys.
     * Example: ['data' => ['balance' => 100]] → ['data.balance' => 100, 'balance' => 100]
     * This allows templates to use either {balance} or {data.balance}.
     */
    private function flattenArray($array, $prefix = '')
    {
        $result = [];
        foreach ($array as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $fullKey));
                $result = array_merge($result, $this->flattenArray($value, ''));
            } else {
                $result[$fullKey] = $value;
                $result[$key]     = $value;
            }
        }
        return $result;
    }

    /**
     * Extract a value from a nested array using dot notation.
     * Example: path = "data.access_token"
     */
    private function extractNestedValue($array, $path)
    {
        $keys  = explode('.', $path);
        $value = $array;

        foreach ($keys as $key) {
            if (!is_array($value) || !isset($value[$key])) {
                return null;
            }
            $value = $value[$key];
        }

        return $value;
    }

    // =========================================================
    // BUTTON HELPER
    // =========================================================

    /**
     * Load button set from DB by context string.
     * Falls back to 'default' context if the requested one is not found.
     * Buttons are sorted by their 'order' field.
     */
    private function getButtons($context)
    {
        $template = ButtonTemplate::where('company_id', $this->company->id)
            ->where('context', $context)
            ->where('is_active', true)
            ->orderBy('priority', 'desc')
            ->first();

        if (!$template) {
            $template = ButtonTemplate::where('company_id', $this->company->id)
                ->where('context', 'default')
                ->where('is_active', true)
                ->first();
        }

        if (!$template) {
            return [];
        }

        $buttons = $template->buttons;

        usort($buttons, fn($a, $b) => ($a['order'] ?? 0) - ($b['order'] ?? 0));

        return array_map(fn($btn) => [
            'label' => $btn['label'],
            'value' => $btn['value'],
        ], $buttons);
    }

    // =========================================================
    // DISPLAY HELPERS
    // =========================================================

    /**
     * Send the welcome message with default buttons.
     * Also clears all session/flow data for the chat.
     */
    private function showMainMenu($chatId)
    {
        $this->clearAllData($chatId);

        $welcome = $this->company->getConfig(
            'welcome_message',
            "Hi! 👋 Welcome to {$this->company->name}. How can I help you today?"
        );

        return $this->sendMessage($chatId, $welcome, $this->getButtons('default'));
    }

    /**
     * Display company contact information (phones, emails, website).
     */
    private function showContactInfo($chatId)
    {
        try {
            $message = "📞 Contact Us:\n\n";

            if ($this->company->phone_numbers) {
                $phones = is_string($this->company->phone_numbers)
                    ? json_decode($this->company->phone_numbers, true)
                    : $this->company->phone_numbers;

                if (is_array($phones)) {
                    foreach ($phones as $location => $phone) {
                        $message .= "📱 {$location}: {$phone}\n";
                    }
                }
            }

            if ($this->company->email_addresses) {
                $emails = is_string($this->company->email_addresses)
                    ? json_decode($this->company->email_addresses, true)
                    : $this->company->email_addresses;

                if (is_array($emails) && !empty($emails)) {
                    $message .= "\n📧 Email:\n";
                    foreach ($emails as $email) {
                        $message .= "✉️ {$email}\n";
                    }
                }
            }

            if ($this->company->website) {
                $message .= "\n🌐 Website:\n{$this->company->website}";
            }

            $message .= "\n\nHow else can I help you? 😊";
            return $this->sendMessage($chatId, $message, $this->getButtons('contact'));
        } catch (\Exception $e) {
            Log::error('Show Contact Error:', ['error' => $e->getMessage()]);
            return $this->sendMessage($chatId, "Sorry, I couldn't load contact information right now.", $this->getButtons('default'));
        }
    }

    /**
     * Display active products from the DB for this company.
     */
    private function showProducts($chatId)
    {
        try {
            $products = DB::table('products')
                ->where('company_id', $this->company->id)
                ->where('is_active', 1)
                ->orderBy('display_order')
                ->get();

            if ($products->isEmpty()) {
                return $this->sendMessage($chatId, "We don't have any products listed yet. Please check back later! 😊", $this->getButtons('default'));
            }

            $message = "🛍️ Our Products:\n\n";
            foreach ($products as $product) {
                $icon     = $product->icon ?? '📦';
                $message .= "{$icon} {$product->name}\n";
                $message .= "   {$product->short_description}\n\n";
            }
            $message .= "\nWould you like to know more about any specific product?";

            return $this->sendMessage($chatId, $message, $this->getButtons('products'));
        } catch (\Exception $e) {
            Log::error('Show Products Error:', ['error' => $e->getMessage()]);
            return $this->sendMessage($chatId, "Sorry, I couldn't load the products right now.", $this->getButtons('default'));
        }
    }

    /**
     * Display active services from the DB for this company.
     */
    private function showServices($chatId)
    {
        try {
            $services = DB::table('services')
                ->where('company_id', $this->company->id)
                ->where('is_active', 1)
                ->orderBy('display_order')
                ->get();

            if ($services->isEmpty()) {
                return $this->sendMessage($chatId, "We don't have any services listed yet. Please check back later! 😊", $this->getButtons('default'));
            }

            $message = "⚙️ Our Services:\n\n";
            foreach ($services as $service) {
                $icon     = $service->icon ?? '🔧';
                $message .= "{$icon} {$service->name}\n";
                $message .= "   {$service->short_description}\n\n";
            }
            $message .= "\nWould you like to know more about any specific service?";

            return $this->sendMessage($chatId, $message, $this->getButtons('services'));
        } catch (\Exception $e) {
            Log::error('Show Services Error:', ['error' => $e->getMessage()]);
            return $this->sendMessage($chatId, "Sorry, I couldn't load the services right now.", $this->getButtons('default'));
        }
    }

    // =========================================================
    // AI FALLBACK
    // =========================================================

    /**
     * Send user message to AI provider and return the response.
     * Maintains a rolling conversation history (last 10 messages) in cache.
     * Selects contextual buttons based on the AI response content.
     */
    private function handleWithAI($chatId, $message)
    {
        try {
            $conversationKey = "conversation_{$this->company->id}_{$chatId}";
            $conversation    = Cache::get($conversationKey, []);

            $conversation[] = ['role' => 'user', 'content' => $message];

            $systemPrompt = $this->buildAIPrompt();
            $response     = $this->callAI($systemPrompt, $conversation);

            $conversation[] = ['role' => 'assistant', 'content' => $response];

            // Keep last 10 messages to avoid token overflow
            if (count($conversation) > 10) {
                $conversation = array_slice($conversation, -10);
            }

            Cache::put($conversationKey, $conversation, 3600);

            $buttons = $this->getContextButtons($message, $response);
            return $this->sendMessage($chatId, $response, $buttons);
        } catch (\Exception $e) {
            Log::error('AI Error:', ['error' => $e->getMessage()]);
            return $this->sendMessage(
                $chatId,
                "I can help you with our products and services. What would you like to know?",
                $this->getButtons('default')
            );
        }
    }

    /**
     * Build the AI system prompt with company info, products, services, and contact.
     */
    private function buildAIPrompt()
    {
        $products = DB::table('products')->where('company_id', $this->company->id)->where('is_active', 1)->get();
        $services = DB::table('services')->where('company_id', $this->company->id)->where('is_active', 1)->get();

        $productsText = '';
        foreach ($products as $p) {
            $productsText .= "• {$p->name}: {$p->short_description}\n";
        }

        $servicesText = '';
        foreach ($services as $s) {
            $servicesText .= "• {$s->name}: {$s->short_description}\n";
        }

        $contactInfo = '';
        if ($this->company->phone_numbers) {
            $phones = is_array($this->company->phone_numbers)
                ? $this->company->phone_numbers
                : json_decode($this->company->phone_numbers, true);

            if (is_array($phones)) {
                foreach ($phones as $loc => $phone) {
                    $contactInfo .= "📞 {$loc}: {$phone}\n";
                }
            }
        }

        return "You are a helpful assistant for {$this->company->name}.

Company Description:
{$this->company->description}

Products:
{$productsText}

Services:
{$servicesText}

Contact:
{$contactInfo}

Guidelines:
- Keep responses SHORT (2-4 sentences)
- Be friendly and helpful
- Use emojis appropriately
- Always end with a helpful question";
    }

    /**
     * Pick context-aware buttons based on keywords in user message + AI response.
     */
    private function getContextButtons($userMessage, $aiResponse)
    {
        $combined = strtolower($userMessage . ' ' . $aiResponse);

        if (stripos($combined, 'product') !== false) return $this->getButtons('products');
        if (stripos($combined, 'service') !== false) return $this->getButtons('services');
        if (stripos($combined, 'contact') !== false || stripos($combined, 'phone') !== false) return $this->getButtons('contact');
        if (stripos($combined, 'track')   !== false) return $this->getButtons('tracking');

        return $this->getButtons('default');
    }

    /**
     * Send prompt to configured AI provider (Groq / OpenAI / Anthropic).
     * Returns the assistant's text reply.
     */
    private function callAI($systemPrompt, $conversation)
    {
        $settings = $this->company->settings;
        $provider = $this->company->ai_provider ?? 'groq';

        $apiKey = match ($provider) {
            'groq'      => $settings?->groq_api_key,
            'openai'    => $settings?->openai_api_key,
            'anthropic' => $settings?->anthropic_api_key,
            default     => $settings?->groq_api_key,
        };

        if (!$apiKey) {
            Log::error('AI API key missing for: ' . $this->company->name);
            throw new \Exception('AI API key not configured in Company Settings.');
        }

        $endpoint = match ($provider) {
            'groq'   => 'https://api.groq.com/openai/v1/chat/completions',
            'openai' => 'https://api.openai.com/v1/chat/completions',
            default  => 'https://api.groq.com/openai/v1/chat/completions',
        };

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ...$conversation,
        ];

        $response = Http::timeout(30)->withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
        ])->post($endpoint, [
            'model'       => $this->company->ai_model       ?? 'llama-3.1-8b-instant',
            'messages'    => $messages,
            'max_tokens'  => $this->company->ai_max_tokens  ?? 300,
            'temperature' => $this->company->ai_temperature ?? 0.7,
        ]);

        if ($response->successful()) {
            return $response->json()['choices'][0]['message']['content'];
        }

        Log::error('AI API Failed:', [
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);

        throw new \Exception('AI API failed: ' . $response->body());
    }

    // =========================================================
    // MESSAGE SENDER
    // =========================================================

    /**
     * Send a message back to the user.
     * Web chat: returns JSON { reply, buttons }.
     * Telegram: calls sendMessage API with optional inline keyboard.
     */
    private function sendMessage($chatId, $text, $buttons = [])
    {
        if (request()->expectsJson()) {
            return response()->json([
                'reply'   => str_replace('*', '', $text),
                'buttons' => $buttons,
            ]);
        }

        // Telegram
        $token = config('services.telegram.bot_token');
        $url   = "https://api.telegram.org/bot{$token}/sendMessage";

        $payload = [
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => 'Markdown',
        ];

        if (!empty($buttons)) {
            $keyboard = [];
            foreach ($buttons as $btn) {
                $keyboard[] = [[
                    'text'          => $btn['label'],
                    'callback_data' => $btn['value'],
                ]];
            }
            $payload['reply_markup'] = json_encode(['inline_keyboard' => $keyboard]);
        }

        file_get_contents($url . '?' . http_build_query($payload));
        return response()->json(['ok' => true]);
    }

    // =========================================================
    // EMAIL NOTIFICATION
    // =========================================================

    /**
     * Send an HTML email notification for form submissions.
     * Uses company-specific SMTP settings loaded fresh from DB.
     */
    private function sendEmailNotification($flowName, $data, $chatId = null, $channel = 'web')
    {
        try {
            $settings = $this->company->settings;

            if (!$settings || !$settings->mail_host) {
                Log::error('❌ Mail settings not configured for: ' . $this->company->name);
                return;
            }

            $toEmail = $this->company->notification_email;
            if (!$toEmail) {
                Log::error('❌ No notification_email set for: ' . $this->company->name);
                return;
            }

            // Build fresh SMTP transport from company settings
            $transport = new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport(
                $settings->mail_host,
                (int) $settings->mail_port,
                strtolower($settings->mail_encryption ?? 'tls') === 'ssl'
            );
            $transport->setUsername($settings->mail_username);
            $transport->setPassword($settings->mail_password);

            $symfonyMailer = new \Symfony\Component\Mailer\Mailer($transport);
            $mailer        = new \Illuminate\Mail\Mailer('company_smtp', $symfonyMailer, app('events'));
            $mailer->alwaysFrom($settings->mail_from_address, $settings->mail_from_name ?? $this->company->name);

            $subject = '🤖 ' . ucwords(str_replace('_', ' ', $flowName))
                . ' — New Chatbot Submission [' . $this->company->name . ']';

            // Build HTML table rows from form data
            $skipFields = ['created_at', 'updated_at', 'company_id'];
            $rows       = '';
            foreach ($data as $key => $value) {
                if (in_array($key, $skipFields)) continue;
                $label  = ucwords(str_replace('_', ' ', $key));
                $rows  .= "<tr>
                    <td style='padding:8px 12px;background:#f8f9fa;font-weight:600;color:#555;width:35%;border-bottom:1px solid #e9ecef;'>{$label}</td>
                    <td style='padding:8px 12px;color:#222;border-bottom:1px solid #e9ecef;'>" . htmlspecialchars((string) $value) . "</td>
                </tr>";
            }

            $channelIcon  = $channel === 'telegram' ? '📲 Telegram' : '🌐 Web Chat';
            $submittedAt  = now()->format('d M Y, h:i A');
            $companyColor = $this->company->primary_color ?? '#4f46e5';
            $companyName  = htmlspecialchars($this->company->name);
            $flowLabel    = ucwords(str_replace('_', ' ', $flowName));
            $sessionInfo  = $chatId ? "<small style='color:#888'>Session: {$chatId}</small>" : '';

            $htmlBody = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:30px 0;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">
        <tr>
          <td style="background:{$companyColor};padding:24px 30px;color:#fff;">
            <h2 style="margin:0;font-size:20px;">🤖 New Chatbot Submission</h2>
            <p style="margin:6px 0 0;opacity:.85;font-size:14px;">{$companyName} &nbsp;·&nbsp; {$channelIcon}</p>
          </td>
        </tr>
        <tr>
          <td style="padding:20px 30px 0;">
            <p style="margin:0;font-size:15px;color:#444;">A new <strong>{$flowLabel}</strong> submission was received via your chatbot.</p>
            <p style="margin:6px 0 0;font-size:12px;color:#999;">{$submittedAt} &nbsp; {$sessionInfo}</p>
          </td>
        </tr>
        <tr>
          <td style="padding:20px 30px;">
            <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e9ecef;border-radius:6px;overflow:hidden;font-size:14px;">
              {$rows}
            </table>
          </td>
        </tr>
        <tr>
          <td style="background:#f8f9fa;padding:16px 30px;font-size:12px;color:#aaa;text-align:center;border-top:1px solid #e9ecef;">
            This is an automated notification from your <strong>{$companyName}</strong> chatbot system.
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;

            // Plain-text fallback
            $textBody  = "New chatbot submission ({$flowLabel})\n";
            $textBody .= "Company: {$companyName} | Channel: {$channelIcon}\n";
            $textBody .= str_repeat('-', 40) . "\n";
            foreach ($data as $key => $value) {
                if (in_array($key, $skipFields)) continue;
                $textBody .= ucwords(str_replace('_', ' ', $key)) . ': ' . $value . "\n";
            }
            $textBody .= str_repeat('-', 40) . "\n";
            $textBody .= "Submitted at: {$submittedAt}\n";

            $mailer->send([], [], function ($msg) use ($toEmail, $subject, $htmlBody, $textBody) {
                $msg->to($toEmail)->subject($subject)->html($htmlBody)->text($textBody);
            });

            Log::info('✅ Email Sent:', [
                'to'      => $toEmail,
                'flow'    => $flowName,
                'company' => $this->company->name,
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Email Send Error:', [
                'error'   => $e->getMessage(),
                'company' => $this->company->name,
            ]);
        }
    }

    // =========================================================
    // SESSION / CACHE MANAGEMENT
    // =========================================================

    /**
     * Clear all flow-related cache for a chat session.
     * Does NOT clear api_token or auth_user — those persist across flows.
     */
    private function clearAllData($chatId)
    {
        $prefix = "{$this->company->id}_{$chatId}";

        Cache::forget("step_{$prefix}");
        Cache::forget("conversation_{$prefix}");
        Cache::forget("flow_fields_{$prefix}");

        // Clear per-step cache keys (supports up to 20 steps)
        for ($i = 0; $i < 20; $i++) {
            Cache::forget("step_{$i}_{$prefix}");
            Cache::forget("field_{$i}_{$prefix}");
        }

        Log::info('🧹 Cleared All Data for Chat:', [
            'chatId'  => $chatId,
            'company' => $this->company->id,
        ]);
    }

    // =========================================================
    // TELEGRAM HELPERS
    // =========================================================

    /**
     * Acknowledge a Telegram inline keyboard callback to dismiss loading state.
     */
    private function answerCallbackQuery($callbackId)
    {
        $token = config('services.telegram.bot_token');
        $url   = "https://api.telegram.org/bot{$token}/answerCallbackQuery";
        file_get_contents($url . '?' . http_build_query(['callback_query_id' => $callbackId]));
    }

    // =========================================================
    // DEVICE / FINGERPRINT HELPERS
    // =========================================================

    /**
     * Generate a SHA-256 fingerprint from User-Agent, IP, language, platform, timezone.
     * Double-hashed to mimic FingerprintJS visitorId behaviour.
     */
    private function generateFingerprint($userAgent, $ipAddress, $identifier)
    {
        $signals = [
            'userAgent' => $userAgent,
            'ip'        => $ipAddress,
            'language'  => 'en-US',
            'platform'  => $this->getPlatformFromUA($userAgent),
            'timezone'  => 'Asia/Kolkata',
        ];

        $visitorId = hash('sha256', implode('|', $signals));
        return hash('sha256', $visitorId);
    }

    /**
     * Build a human-readable device name string: Browser(OS-type).
     * Example: "Chrome(Windows-desktop)"
     */
    private function getDeviceName($userAgent)
    {
        $browser = $this->parseBrowser($userAgent);
        $os      = $this->parseOS($userAgent);
        $type    = $this->parseDeviceType($userAgent);
        return "{$browser}({$os}-{$type})";
    }

    private function parseBrowser($ua)
    {
        if (str_contains($ua, 'Edg/'))    return 'Microsoft Edge';
        if (str_contains($ua, 'Chrome'))  return 'Chrome';
        if (str_contains($ua, 'Firefox')) return 'Firefox';
        if (str_contains($ua, 'Safari'))  return 'Safari';
        if (str_contains($ua, 'Opera'))   return 'Opera';
        return 'Unknown Browser';
    }

    private function parseOS($ua)
    {
        if (str_contains($ua, 'Windows')) return 'Windows';
        if (str_contains($ua, 'Mac'))     return 'macOS';
        if (str_contains($ua, 'Android')) return 'Android';
        if (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) return 'iOS';
        if (str_contains($ua, 'Linux'))   return 'Linux';
        return 'Unknown OS';
    }

    private function parseDeviceType($ua)
    {
        if (str_contains($ua, 'Mobile'))  return 'mobile';
        if (str_contains($ua, 'Tablet') || str_contains($ua, 'iPad')) return 'tablet';
        return 'desktop';
    }

    private function getPlatformFromUA($ua)
    {
        if (str_contains($ua, 'Win64'))   return 'Win64';
        if (str_contains($ua, 'Win32'))   return 'Win32';
        if (str_contains($ua, 'Mac'))     return 'MacIntel';
        if (str_contains($ua, 'Android')) return 'Android';
        if (str_contains($ua, 'Linux'))   return 'Linux';
        return 'Unknown';
    }
}
