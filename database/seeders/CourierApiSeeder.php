<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\ApiConnection;
use App\Models\ApiEndpoint;
use App\Models\ChatbotIntegration;

class CourierApiSeeder extends Seeder
{
    public function run(): void
    {
        echo "🚀 Setting up Courier API Integration...\n\n";

        // Get Milestone IT company
        $company = Company::where('slug', 'milestone-it')->first();

        if (!$company) {
            echo "❌ Company not found. Please run MilestoneDataSeeder first.\n";
            return;
        }

        // ============================================
        // 1. Create API Connection
        // ============================================
        $apiConnection = ApiConnection::create([
            'company_id' => $company->id,
            'connection_name' => 'Courier Management System',
            'api_type' => 'rest',
            'base_url' => env('COURIER_API_URL', 'http://localhost:8000'),
            'api_key' => env('COURIER_API_KEY', ''),
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ],
            'auth_config' => [
                'type' => 'bearer',
                'token_field' => 'api_key'
            ],
            'is_active' => true
        ]);

        echo "✅ API Connection created: {$apiConnection->connection_name}\n";

        // ============================================
        // 2. Create API Endpoints
        // ============================================
        $endpoints = [
            [
                'endpoint_name' => 'Get Medicines',
                'endpoint_path' => '/api/medicines',
                'method' => 'GET',
                'cache_key_prefix' => 'medicines',
                'cache_duration' => 3600, // 1 hour
            ],
            [
                'endpoint_name' => 'Get Medicine Details',
                'endpoint_path' => '/api/medicines/{id}',
                'method' => 'GET',
                'cache_key_prefix' => 'medicine',
                'cache_duration' => 3600,
            ],
            [
                'endpoint_name' => 'Get Orders by Phone',
                'endpoint_path' => '/api/orders/phone/{phone}',
                'method' => 'GET',
                'cache_key_prefix' => 'orders_phone',
                'cache_duration' => 300, // 5 minutes
            ],
            [
                'endpoint_name' => 'Get Order Details',
                'endpoint_path' => '/api/orders/{id}',
                'method' => 'GET',
                'cache_key_prefix' => 'order',
                'cache_duration' => 300,
            ],
            [
                'endpoint_name' => 'Track Order',
                'endpoint_path' => '/api/track/{order_number}',
                'method' => 'GET',
                'cache_key_prefix' => 'track',
                'cache_duration' => 180, // 3 minutes
            ],
            [
                'endpoint_name' => 'Get Drivers',
                'endpoint_path' => '/api/drivers',
                'method' => 'GET',
                'cache_key_prefix' => 'drivers',
                'cache_duration' => 1800, // 30 minutes
            ],
            [
                'endpoint_name' => 'Get Drivers by City',
                'endpoint_path' => '/api/drivers/city/{city_id}',
                'method' => 'GET',
                'cache_key_prefix' => 'drivers_city',
                'cache_duration' => 1800,
            ],
            [
                'endpoint_name' => 'Get Cities',
                'endpoint_path' => '/api/cities',
                'method' => 'GET',
                'cache_key_prefix' => 'cities',
                'cache_duration' => 7200, // 2 hours
            ]
        ];

        foreach ($endpoints as $endpointData) {
            ApiEndpoint::create(array_merge($endpointData, [
                'api_connection_id' => $apiConnection->id,
                'is_active' => true
            ]));
        }

        echo "✅ API Endpoints created: " . count($endpoints) . " endpoints\n";

        // ============================================
        // 3. Create Chatbot Integration Config
        // ============================================
        $integration = ChatbotIntegration::create([
            'company_id' => $company->id,
            'integration_type' => 'courier',
            'enable_order_tracking' => true,
            'enable_medicine_info' => true,
            'enable_driver_info' => false, // Usually not needed for customers
            'features_config' => [
                'allow_order_creation' => false, // Set true if chatbot can create orders
                'show_driver_location' => false,
                'show_delivery_estimate' => true,
                'enable_notifications' => true
            ],
            'is_active' => true
        ]);

        echo "✅ Chatbot Integration created\n";

        // ============================================
        // 4. Update Company Config with Courier Features
        // ============================================
        $company->chatbotConfigs()->create([
            'config_key' => 'courier_greeting',
            'config_value' => "Hi! 👋 I can help you:\n\n• Track your order\n• Check medicine availability\n• Get delivery updates\n\nWhat would you like to know?",
            'category' => 'courier'
        ]);

        $company->chatbotConfigs()->create([
            'config_key' => 'order_tracking_enabled',
            'config_value' => 'true',
            'category' => 'courier'
        ]);

        echo "✅ Courier-specific configs created\n\n";

        echo "🎉 Courier API Integration setup complete!\n";
        echo "📊 Summary:\n";
        echo "   - API Connection: {$apiConnection->connection_name}\n";
        echo "   - Endpoints: " . count($endpoints) . "\n";
        echo "   - Integration: {$integration->integration_type}\n\n";
        echo "⚙️  Next Steps:\n";
        echo "   1. Set COURIER_API_URL in .env\n";
        echo "   2. Set COURIER_API_KEY in .env\n";
        echo "   3. Test API connection\n";
    }
}