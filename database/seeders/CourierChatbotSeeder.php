<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Product;
use App\Models\Service;
use App\Models\ChatbotConfig;
use App\Models\ButtonTemplate;

class CourierChatbotSeeder extends Seeder
{
    public function run(): void
    {
        echo "🚚 Starting Courier Management Chatbot setup...\n\n";

        // ============================================
        // 1. Create Courier Company
        // ============================================
        $company = Company::create([
            'name' => 'Swift Courier & Delivery',
            'slug' => 'swift-courier',
            'description' => 'Fast and reliable courier and delivery services across UAE. We ensure your packages reach their destination safely and on time.',
            'phone_numbers' => [
                'Customer Service' => '+971 4 123 4567',
                'WhatsApp' => '+971 50 123 4567',
                'Toll Free' => '800 COURIER'
            ],
            'email_addresses' => [
                'support@swiftcourier.ae',
                'track@swiftcourier.ae'
            ],
            'website' => 'https://swiftcourier.ae',
            'primary_color' => '#FF6B35',
            'secondary_color' => '#004E89',
            'bot_name' => 'Swift Assistant',
            'bot_avatar' => '📦',
            'ai_provider' => 'groq',
            'ai_model' => 'llama-3.1-8b-instant',
            'ai_temperature' => 0.7,
            'ai_max_tokens' => 300,
            'notification_email' => 'support@swiftcourier.ae',
            'is_active' => true
        ]);
        
        echo "✅ Company created: {$company->name}\n\n";

        // ============================================
        // 2. Create Products (Delivery Services)
        // ============================================
        $products = [
            [
                'name' => 'Same Day Delivery',
                'slug' => 'same-day-delivery',
                'short_description' => 'Get your package delivered within the same day across UAE.',
                'full_description' => 'Our same-day delivery service ensures your urgent packages reach their destination within hours. Available for all major cities in UAE.',
                'keywords' => ['same day', 'urgent', 'express', 'fast delivery', 'quick'],
                'demo_available' => false,
                'icon' => '⚡',
                'display_order' => 1,
                'is_featured' => true,
                'is_active' => true
            ],
            [
                'name' => 'Next Day Delivery',
                'slug' => 'next-day-delivery',
                'short_description' => 'Reliable next-day delivery service at affordable rates.',
                'full_description' => 'Schedule your delivery for the next day with our cost-effective and reliable service. Perfect for non-urgent shipments.',
                'keywords' => ['next day', 'tomorrow', 'standard delivery'],
                'demo_available' => false,
                'icon' => '📦',
                'display_order' => 2,
                'is_featured' => true,
                'is_active' => true
            ],
            [
                'name' => 'International Shipping',
                'slug' => 'international-shipping',
                'short_description' => 'Send packages worldwide with our international shipping service.',
                'full_description' => 'We handle customs, documentation, and ensure safe delivery of your international shipments to over 200 countries.',
                'keywords' => ['international', 'overseas', 'worldwide', 'global shipping'],
                'demo_available' => false,
                'icon' => '✈️',
                'display_order' => 3,
                'is_active' => true
            ],
            [
                'name' => 'Bulk Delivery',
                'slug' => 'bulk-delivery',
                'short_description' => 'Special rates for bulk orders and business deliveries.',
                'full_description' => 'Custom solutions for businesses with regular delivery needs. Competitive pricing for bulk shipments.',
                'keywords' => ['bulk', 'business', 'wholesale', 'corporate'],
                'demo_available' => false,
                'icon' => '📊',
                'display_order' => 4,
                'is_active' => true
            ]
        ];

        foreach ($products as $productData) {
            Product::create(array_merge($productData, ['company_id' => $company->id]));
        }
        
        echo "✅ Delivery services created: " . count($products) . " services\n\n";

        // ============================================
        // 3. Create Services (Support Services)
        // ============================================
        $services = [
            [
                'name' => 'Order Tracking',
                'slug' => 'order-tracking',
                'short_description' => 'Track your package in real-time with our advanced tracking system.',
                'full_description' => 'Get live updates on your package location, estimated delivery time, and delivery status.',
                'keywords' => ['track', 'tracking', 'where is my order', 'order status', 'location'],
                'enquiry_available' => true,
                'icon' => '📍',
                'display_order' => 1,
                'is_active' => true
            ],
            [
                'name' => 'Customer Support',
                'slug' => 'customer-support',
                'short_description' => 'Get help from our dedicated customer support team.',
                'full_description' => '24/7 customer support for all your queries, issues, and assistance needs.',
                'keywords' => ['support', 'help', 'customer service', 'complaint', 'issue'],
                'enquiry_available' => true,
                'icon' => '💬',
                'display_order' => 2,
                'is_active' => true
            ],
            [
                'name' => 'Pickup Request',
                'slug' => 'pickup-request',
                'short_description' => 'Schedule a pickup from your location at your convenience.',
                'full_description' => 'Request a pickup and our driver will collect your package from your doorstep.',
                'keywords' => ['pickup', 'collect', 'schedule pickup'],
                'enquiry_available' => true,
                'icon' => '🚗',
                'display_order' => 3,
                'is_active' => true
            ],
            [
                'name' => 'Rate Calculator',
                'slug' => 'rate-calculator',
                'short_description' => 'Calculate delivery charges based on weight and destination.',
                'full_description' => 'Get instant quotes for your delivery based on package dimensions, weight, and destination.',
                'keywords' => ['rate', 'price', 'cost', 'charges', 'quote'],
                'enquiry_available' => false,
                'icon' => '💰',
                'display_order' => 4,
                'is_active' => true
            ]
        ];

        foreach ($services as $serviceData) {
            Service::create(array_merge($serviceData, ['company_id' => $company->id]));
        }
        
        echo "✅ Support services created: " . count($services) . " services\n\n";

        // ============================================
        // 4. Create Chatbot Configs
        // ============================================
        $configs = [
            // Welcome & General Messages
            [
                'config_key' => 'welcome_message',
                'config_value' => "Hi there! 👋\n\nWelcome to Swift Courier & Delivery!\n\n📦 Track your order\n💬 Get customer support\n🚚 Schedule pickups\n\nHow can I help you today?",
                'category' => 'messages'
            ],
            [
                'config_key' => 'goodbye_message',
                'config_value' => "Thank you for choosing Swift Courier! 😊\n\nHave a great day! Feel free to reach out anytime.",
                'category' => 'messages'
            ],
            
            // Tracking Messages
            [
                'config_key' => 'tracking_prompt',
                'config_value' => "Sure! I can help you track your order. 📦\n\nPlease provide your tracking number or order ID.\n\nExample: ON#20251103#006",
                'category' => 'tracking'
            ],
            [
                'config_key' => 'tracking_not_found',
                'config_value' => "Sorry, I couldn't find any order with that number. 😔\n\nPlease check:\n• The tracking number is correct\n• There are no extra spaces\n• The order was placed with us\n\nWould you like to try again?",
                'category' => 'tracking'
            ],
            
            // Support Messages
            [
                'config_key' => 'support_greeting',
                'config_value' => "I'm here to help! 💬\n\nPlease describe your issue or question, and I'll assist you right away.\n\nYou can also call us at:\n📞 +971 4 123 4567",
                'category' => 'support'
            ],
            [
                'config_key' => 'support_submitted',
                'config_value' => "Your request has been submitted! ✅\n\nOur support team will get back to you shortly.\n\nTicket Reference: #{ticket_id}\n\nIs there anything else I can help with?",
                'category' => 'support'
            ],
            
            // Error Messages
            [
                'config_key' => 'error_message',
                'config_value' => "Oops! Something went wrong. 😔\n\nPlease try again or contact our support team:\n📞 +971 4 123 4567\n✉️ support@swiftcourier.ae",
                'category' => 'messages'
            ],
            
            // Business Hours
            [
                'config_key' => 'business_hours',
                'config_value' => "⏰ Our Business Hours:\n\nSunday - Thursday: 8:00 AM - 8:00 PM\nFriday: 9:00 AM - 6:00 PM\nSaturday: 9:00 AM - 5:00 PM\n\n📞 24/7 Customer Support: +971 4 123 4567",
                'category' => 'info'
            ]
        ];

        foreach ($configs as $configData) {
            ChatbotConfig::create(array_merge($configData, ['company_id' => $company->id]));
        }
        
        echo "✅ Chatbot configs created: " . count($configs) . " configs\n\n";

        // ============================================
        // 5. Create Button Templates
        // ============================================
        $buttonTemplates = [
            // Main Menu
            [
                'template_name' => 'main_menu',
                'context' => 'default',
                'buttons' => [
                    ['label' => '📦 Track Order', 'value' => 'track order', 'order' => 1],
                    ['label' => '💬 Customer Support', 'value' => 'customer support', 'order' => 2],
                    ['label' => '🚚 Our Services', 'value' => 'what services do you provide', 'order' => 3],
                    ['label' => '☎ Contact Us', 'value' => 'how can I contact you', 'order' => 4]
                ],
                'priority' => 10,
                'is_active' => true
            ],
            
            // After Tracking
            [
                'template_name' => 'after_tracking',
                'context' => 'tracking',
                'buttons' => [
                    ['label' => '📦 Track Another Order', 'value' => 'track order', 'order' => 1],
                    ['label' => '💬 Need Help?', 'value' => 'customer support', 'order' => 2],
                    ['label' => '🏠 Main Menu', 'value' => 'menu', 'order' => 3]
                ],
                'priority' => 9,
                'is_active' => true
            ],
            
            // Support Context
            [
                'template_name' => 'support_menu',
                'context' => 'support',
                'buttons' => [
                    ['label' => '📦 Track My Order', 'value' => 'track order', 'order' => 1],
                    ['label' => '📞 Call Support', 'value' => 'how can I contact you', 'order' => 2],
                    ['label' => '🏠 Main Menu', 'value' => 'menu', 'order' => 3]
                ],
                'priority' => 8,
                'is_active' => true
            ],
            
            // Services Context
            [
                'template_name' => 'services_menu',
                'context' => 'services',
                'buttons' => [
                    ['label' => '🚗 Request Pickup', 'value' => 'schedule pickup', 'order' => 1],
                    ['label' => '💰 Get Quote', 'value' => 'calculate rate', 'order' => 2],
                    ['label' => '📦 Track Order', 'value' => 'track order', 'order' => 3],
                    ['label' => '🏠 Main Menu', 'value' => 'menu', 'order' => 4]
                ],
                'priority' => 7,
                'is_active' => true
            ],
            
            // Contact Context
            [
                'template_name' => 'contact_options',
                'context' => 'contact',
                'buttons' => [
                    ['label' => '📦 Track Order', 'value' => 'track order', 'order' => 1],
                    ['label' => '💬 Customer Support', 'value' => 'customer support', 'order' => 2],
                    ['label' => '🏠 Main Menu', 'value' => 'menu', 'order' => 3]
                ],
                'priority' => 6,
                'is_active' => true
            ],
            
            // Navigation (Generic)
            [
                'template_name' => 'navigation',
                'context' => 'navigation',
                'buttons' => [
                    ['label' => '🏠 Main Menu', 'value' => 'menu', 'order' => 1],
                    ['label' => '❌ Exit', 'value' => 'exit', 'order' => 2]
                ],
                'priority' => 5,
                'is_active' => true
            ]
        ];

        foreach ($buttonTemplates as $templateData) {
            ButtonTemplate::create(array_merge($templateData, ['company_id' => $company->id]));
        }
        
        echo "✅ Button templates created: " . count($buttonTemplates) . " templates\n\n";

        echo "🎉 Courier Chatbot setup complete!\n";
        echo "📊 Summary:\n";
        echo "   - Company: {$company->name}\n";
        echo "   - Delivery Services: " . count($products) . "\n";
        echo "   - Support Services: " . count($services) . "\n";
        echo "   - Configs: " . count($configs) . "\n";
        echo "   - Button Templates: " . count($buttonTemplates) . "\n\n";
        echo "🌐 Access URL: /swift-courier/chat\n";
        echo "📱 WhatsApp: Configure webhook for courier bot\n\n";
    }
}