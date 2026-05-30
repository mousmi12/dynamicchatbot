<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\ButtonTemplate;
use App\Models\ChatbotFlow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    private $company;

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

    /**
     * Handle incoming requests
     */
    public function handle(Request $request)
    {
        if ($request->getMethod() === 'OPTIONS') {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', '*');
        }

        try {
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

    /**
     * Handle Web Chat
     */
    public function handleWebChat(Request $request)
    {
        try {
            $path     = $request->path();
            $segments = explode('/', $path);
            $companySlug = $segments[0] ?? null;

            // ✅ 'settings' കൂടെ load ചെയ്യുന്നു
            $this->company = Company::where('slug', $companySlug)
                ->where('is_active', true)
                ->with(['chatbotConfigs', 'buttonTemplates', 'chatbotFlows', 'settings'])
                ->first();

            if (!$this->company) {
                $this->company = Company::where('is_active', true)
                    ->with(['chatbotConfigs', 'buttonTemplates', 'chatbotFlows', 'settings'])
                    ->first();
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
     * Handle Telegram
     */
    private function handleTelegram(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        Log::info('Telegram RAW:', $data);

        // ✅ 'settings' കൂടെ load ചെയ്യുന്നു
        $this->company = Company::where('is_active', true)
            ->with(['chatbotConfigs', 'buttonTemplates', 'chatbotFlows', 'settings'])
            ->firstOrFail();

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

    /**
     * Process Message - Core Logic
     */
    private function processMessage($chatId, $msg)
    {
        $originalMsg = $msg;
        $msg         = strtolower(trim($msg));

        $stepKey     = "step_{$this->company->id}_{$chatId}";
        $currentStep = Cache::get($stepKey);

        Log::info('Process Message:', [
            'company'      => $this->company->name,
            'chatId'       => $chatId,
            'msg'          => $msg,
            'current_step' => $currentStep,
        ]);

        // ── PRIORITY 1: Global Commands ────────────────────────────────
        if (in_array($msg, ['hi', 'hello', 'hai', 'hey', '/start', 'start'])) {
            $this->clearAllData($chatId);
            return $this->showMainMenu($chatId);
        }

        if ($msg === 'menu') {
            $this->clearAllData($chatId);
            return $this->showMainMenu($chatId);
        }

        if ($msg === 'back') {
            $this->clearAllData($chatId);
            return $this->showMainMenu($chatId);
        }

        if ($msg === 'exit') {
            $this->clearAllData($chatId);
            $goodbye = $this->company->getConfig('goodbye_message', 'Thank you! 👋');
            return $this->sendMessage($chatId, $goodbye);
        }

        // ── PRIORITY 2: Continue Existing Flow ────────────────────────
        if ($currentStep) {
            return $this->continueFlow($chatId, $originalMsg, $currentStep);
        }

        // ── PRIORITY 3: Detect and Start New Flow ─────────────────────
        $flow = $this->detectFlow($msg);
        if ($flow) {
            Log::info('Starting Flow:', ['flow' => $flow->flow_name]);
            return $this->startFlow($chatId, $flow);
        }

        // ── PRIORITY 4: Direct Display Commands ───────────────────────
        if (stripos($msg, 'product') !== false) {
            return $this->showProducts($chatId);
        }

        if (stripos($msg, 'service') !== false) {
            return $this->showServices($chatId);
        }

        if (
            stripos($msg, 'contact') !== false ||
            stripos($msg, 'phone') !== false ||
            stripos($msg, 'call') !== false
        ) {
            return $this->showContactInfo($chatId);
        }

        // ── PRIORITY 5: AI Fallback ────────────────────────────────────
        Log::info('Using AI Fallback');
        return $this->handleWithAI($chatId, $originalMsg);
    }

    /**
     * Show Contact Information
     */
    private function showContactInfo($chatId)
    {
        try {
            $message = "📞 **Contact Us:**\n\n";

            if ($this->company->phone_numbers) {
                $phones = is_string($this->company->phone_numbers)
                    ? json_decode($this->company->phone_numbers, true)
                    : $this->company->phone_numbers;

                if (is_array($phones) && !empty($phones)) {
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
            return $this->sendMessage(
                $chatId,
                "Sorry, I couldn't load contact information right now.",
                $this->getButtons('default')
            );
        }
    }

    /**
     * Show Products
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
                return $this->sendMessage(
                    $chatId,
                    "We don't have any products listed yet. Please check back later! 😊",
                    $this->getButtons('default')
                );
            }

            $message = "🛍️ **Our Products:**\n\n";
            foreach ($products as $product) {
                $icon     = $product->icon ?? '📦';
                $message .= "{$icon} **{$product->name}**\n";
                $message .= "   {$product->short_description}\n\n";
            }
            $message .= "\nWould you like to know more about any specific product?";

            return $this->sendMessage($chatId, $message, $this->getButtons('products'));
        } catch (\Exception $e) {
            Log::error('Show Products Error:', ['error' => $e->getMessage()]);
            return $this->sendMessage(
                $chatId,
                "Sorry, I couldn't load the products right now. Please try again later.",
                $this->getButtons('default')
            );
        }
    }

    /**
     * Show Services
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
                return $this->sendMessage(
                    $chatId,
                    "We don't have any services listed yet. Please check back later! 😊",
                    $this->getButtons('default')
                );
            }

            $message = "⚙️ **Our Services:**\n\n";
            foreach ($services as $service) {
                $icon     = $service->icon ?? '🔧';
                $message .= "{$icon} **{$service->name}**\n";
                $message .= "   {$service->short_description}\n\n";
            }
            $message .= "\nWould you like to know more about any specific service?";

            return $this->sendMessage($chatId, $message, $this->getButtons('services'));
        } catch (\Exception $e) {
            Log::error('Show Services Error:', ['error' => $e->getMessage()]);
            return $this->sendMessage(
                $chatId,
                "Sorry, I couldn't load the services right now. Please try again later.",
                $this->getButtons('default')
            );
        }
    }

    /**
     * Detect which flow to start based on triggers
     */
    private function detectFlow($message)
    {
        $flows = ChatbotFlow::where('company_id', $this->company->id)
            ->where('is_active', true)
            ->orderBy('priority', 'desc')
            ->get();

        foreach ($flows as $flow) {
            $triggers = $flow->triggers ?? [];
            foreach ($triggers as $trigger) {
                if (stripos($message, $trigger) !== false) {
                    Log::info('Flow Detected:', ['flow' => $flow->flow_name, 'trigger' => $trigger]);
                    return $flow;
                }
            }
        }

        return null;
    }

    /**
     * Start a new flow
     */
    private function startFlow($chatId, $flow)
    {
        $steps = $flow->steps;

        if (empty($steps)) {
            Log::error('No steps in flow:', ['flow' => $flow->flow_name]);
            return $this->showMainMenu($chatId);
        }

        $firstStep = $steps[0];

        Cache::put("step_{$this->company->id}_{$chatId}", [
            'flow_id'     => $flow->id,
            'flow_name'   => $flow->flow_name,
            'flow_type'   => $flow->flow_type,
            'step_index'  => 0,
            'data_config' => $flow->data_config,
        ], 3600);

        return $this->sendMessage($chatId, $firstStep['message'], $this->getButtons('navigation'));
    }

    /**
     * Continue existing flow
     */
    private function continueFlow($chatId, $userInput, $stepState)
    {
        if (in_array(strtolower($userInput), ['menu', 'exit', 'back'])) {
            $this->clearAllData($chatId);
            return $this->processMessage($chatId, $userInput);
        }

        $flow = ChatbotFlow::find($stepState['flow_id']);

        if (!$flow) {
            Log::error('Flow not found:', ['flow_id' => $stepState['flow_id']]);
            return $this->showMainMenu($chatId);
        }

        $steps            = $flow->steps;
        $currentStepIndex = $stepState['step_index'];
        $currentStepData  = $steps[$currentStepIndex];

        $cacheKey  = $currentStepData['cache_key'] ?? "step_{$currentStepIndex}";
        $fieldName = $currentStepData['field_name'] ?? $cacheKey;

        Cache::put("{$cacheKey}_{$this->company->id}_{$chatId}", $userInput, 3600);

        $fieldsKey = "flow_fields_{$this->company->id}_{$chatId}";
        $fields    = Cache::get($fieldsKey, []);
        $fields[$fieldName] = $userInput;
        Cache::put($fieldsKey, $fields, 3600);

        if ($currentStepIndex >= count($steps) - 1) {
            return $this->completeFlow($chatId, $flow, $stepState);
        }

        $nextStepIndex          = $currentStepIndex + 1;
        $nextStep               = $steps[$nextStepIndex];
        $stepState['step_index'] = $nextStepIndex;

        Cache::put("step_{$this->company->id}_{$chatId}", $stepState, 3600);

        return $this->sendMessage($chatId, $nextStep['message'], $this->getButtons('navigation'));
    }

    /**
     * Complete flow and execute action
     */
    private function completeFlow($chatId, $flow, $stepState)
    {
        $flowType   = $flow->flow_type;
        $dataConfig = $stepState['data_config'] ?? $flow->data_config;

        Log::info('Completing Flow:', ['flow' => $flow->flow_name, 'type' => $flowType]);

        if ($flowType === 'data_query') {
            return $this->executeDataQuery($chatId, $flow, $dataConfig);
        }

        if ($flowType === 'form_collection') {
            return $this->executeFormSave($chatId, $flow, $dataConfig);
        }

        $this->clearAllData($chatId);
        return $this->showMainMenu($chatId);
    }

    /**
     * Execute data query
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

            $query = DB::table($table);
            foreach ($searchColumns as $column) {
                $query->orWhere($column, $searchValue);
            }
            $result = $query->first();

            if ($result) {
                $template  = $config['response_template'] ?? 'Result found';
                $response  = $this->formatTemplate($template, (array) $result);
                $buttons   = $this->getButtons($config['success_buttons'] ?? 'default');
            } else {
                $response = $config['not_found_message'] ?? 'No results found';
                $buttons  = $this->getButtons($config['not_found_buttons'] ?? 'default');
            }

            $this->clearAllData($chatId);
            return $this->sendMessage($chatId, $response, $buttons);
        } catch (\Exception $e) {
            Log::error('Data Query Error:', ['error' => $e->getMessage()]);
            $errorMsg = $this->company->getConfig('error_message', 'Something went wrong');
            return $this->sendMessage($chatId, $errorMsg, $this->getButtons('default'));
        }
    }

    /**
     * Execute form save
     */
    private function executeFormSave($chatId, $flow, $config)
    {
        $fieldsKey = "flow_fields_{$this->company->id}_{$chatId}";
        $fields    = Cache::get($fieldsKey, []);

        if (empty($fields)) {
            Log::error('No form data found');
            return $this->showMainMenu($chatId);
        }

        try {
            $fields['company_id'] = $this->company->id;

            $table = $config['save_to_table'];
            DB::table($table)->insert($fields);

            Log::info('Form Saved:', ['table' => $table, 'data' => $fields]);

            if (isset($config['send_email']) && $config['send_email']) {
                $this->sendEmailNotification($flow->flow_name, $fields);
            }

            $response = $config['success_message'] ?? 'Successfully submitted!';
            $buttons  = $this->getButtons($config['success_buttons'] ?? 'default');

            $this->clearAllData($chatId);
            return $this->sendMessage($chatId, $response, $buttons);
        } catch (\Exception $e) {
            Log::error('Form Save Error:', ['error' => $e->getMessage()]);
            $errorMsg = $this->company->getConfig('error_message', 'Something went wrong');
            return $this->sendMessage($chatId, $errorMsg, $this->getButtons('default'));
        }
    }

    /**
     * Format template with data
     */
    private function formatTemplate($template, $data)
    {
        foreach ($data as $key => $value) {
            $template = str_replace("{{$key}}", $value, $template);
        }
        return $template;
    }

    /**
     * Get buttons from database
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

        if (!$template) return [];

        $buttons = $template->buttons;
        usort($buttons, fn($a, $b) => ($a['order'] ?? 0) - ($b['order'] ?? 0));

        return array_map(fn($btn) => [
            'label' => $btn['label'],
            'value' => $btn['value'],
        ], $buttons);
    }

    /**
     * Show Main Menu
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
     * Handle with AI
     */
    private function handleWithAI($chatId, $message)
    {
        Log::info('handleWithAI called:', [
            'company'  => $this->company->name,
            'settings' => $this->company->settings ? 'loaded' : 'NULL',
            'api_key'  => $this->company->settings?->groq_api_key ? 'has key' : 'NO KEY',
            'provider' => $this->company->ai_provider,
        ]);
        try {
            $conversationKey = "conversation_{$this->company->id}_{$chatId}";
            $conversation    = Cache::get($conversationKey, []);

            $conversation[] = ['role' => 'user', 'content' => $message];

            $systemPrompt = $this->buildAIPrompt();
            $response     = $this->callAI($systemPrompt, $conversation);

            $conversation[] = ['role' => 'assistant', 'content' => $response];

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
     * Build AI system prompt dynamically
     */
    private function buildAIPrompt()
    {
     // ✅ getActiveProducts() 
    $products = DB::table('products')
        ->where('company_id', $this->company->id)
        ->where('is_active', 1)
        ->get();

    $services = DB::table('services')
        ->where('company_id', $this->company->id)
        ->where('is_active', 1)
        ->get();

    $productsText = '';
    foreach ($products as $product) {
        $productsText .= "• {$product->name}: {$product->short_description}\n";
    }

    $servicesText = '';
    foreach ($services as $service) {
        $servicesText .= "• {$service->name}: {$service->short_description}\n";
    }

    $contactInfo = '';
    if ($this->company->phone_numbers) {
        $phones = is_array($this->company->phone_numbers)
            ? $this->company->phone_numbers
            : json_decode($this->company->phone_numbers, true);

        if (is_array($phones)) {
            foreach ($phones as $location => $phone) {
                $contactInfo .= "📞 {$location}: {$phone}\n";
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
     * Get context-aware buttons for AI responses
     */
    private function getContextButtons($userMessage, $aiResponse)
    {
        $combined = strtolower($userMessage . ' ' . $aiResponse);

        if (stripos($combined, 'product') !== false) return $this->getButtons('products');
        if (stripos($combined, 'service') !== false) return $this->getButtons('services');
        if (stripos($combined, 'contact') !== false || stripos($combined, 'phone') !== false) {
            return $this->getButtons('contact');
        }
        if (stripos($combined, 'track') !== false) return $this->getButtons('tracking');

        return $this->getButtons('default');
    }

    /**
     * ✅ Call AI — DB-ൽ നിന്ന് API key എടുക്കുന്നു
     */
    private function callAI($systemPrompt, $conversation)
    {
        // DB-ൽ നിന്ന് company settings load
        $settings = $this->company->settings;
        $provider = $this->company->ai_provider ?? 'groq';
        Log::info('callAI called:', [
            'provider'     => $provider,
            'has_settings' => $settings ? 'yes' : 'no',
            'api_key'      => $settings?->groq_api_key ? 'has key' : 'NO KEY',
        ]);
        // Provider അനുസരിച്ച് API key
        $apiKey = match ($provider) {
            'groq'      => $settings?->groq_api_key,
            'openai'    => $settings?->openai_api_key,
            'anthropic' => $settings?->anthropic_api_key,
            default     => $settings?->groq_api_key,
        };

        if (!$apiKey) {
            Log::error('AI API key missing for company: ' . $this->company->name);
            throw new \Exception('AI API key not configured. Please add it in Company Settings.');
        }

        // Provider അനുസരിച്ച് endpoint
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
            'provider' => $provider,
            'status'   => $response->status(),
            'body'     => $response->body(),
        ]);

        throw new \Exception('AI API failed: ' . $response->body());
    }

    /**
     * Send Message
     */
    private function sendMessage($chatId, $text, $buttons = [])
    {
        if (request()->expectsJson()) {
            return response()->json([
                'reply'   => str_replace('*', '', $text),
                'buttons' => $buttons,
            ]);
        }

        // ✅ Telegram token — DB-ൽ നിന്ന് എടുക്കുന്നു
        $settings = $this->company->settings;
        $token    = $settings?->telegram_bot_token ?? config('services.telegram.bot_token');

        if (!$token) {
            Log::error('Telegram token missing for company: ' . $this->company->name);
            return response()->json(['ok' => false, 'error' => 'Telegram token not configured']);
        }

        $url     = "https://api.telegram.org/bot{$token}/sendMessage";
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

    /**
     * Send email notification
     */
    private function sendEmailNotification($flowName, $data)
    {
        Log::info('Email Notification:', ['flow' => $flowName, 'data' => $data]);
    }

    /**
     * Clear all session data
     */
    private function clearAllData($chatId)
    {
        $prefix = "{$this->company->id}_{$chatId}";

        Cache::forget("step_{$prefix}");
        Cache::forget("conversation_{$prefix}");
        Cache::forget("flow_fields_{$prefix}");

        for ($i = 0; $i < 10; $i++) {
            Cache::forget("step_{$i}_{$prefix}");
        }
    }

    private function answerCallbackQuery($callbackId)
    {
        $settings = $this->company->settings;
        $token    = $settings?->telegram_bot_token ?? config('services.telegram.bot_token');

        $url = "https://api.telegram.org/bot{$token}/answerCallbackQuery";

        file_get_contents($url . '?' . http_build_query([
            'callback_query_id' => $callbackId,
        ]));
    }
}