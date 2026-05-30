<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Product;
use App\Models\Service;
use App\Models\ChatbotConfig;
use App\Models\ButtonTemplate;

class MilestoneDataSeeder extends Seeder
{
    public function run(): void
    {
        echo "🚀 Starting Milestone IT data seeding...\n\n";

        // ============================================
        // 1. Create Company
        // ============================================
        $company = Company::create([
            'name' => 'Milestone Innovative Technologies',
            'slug' => 'milestone-it',
            'description' => 'We\'re a software development company working with clients in India and the UAE. We build business software, web applications, and digital solutions to make day-to-day operations easier.',
            'phone_numbers' => [
                'India' => '+91 7994222273',
                'India 2' => '+91 7902255500',
                'UAE' => '+971 26266362'
            ],
            'email_addresses' => [
                'info@milestoneit.net',
                'info@milestoneit.ae'
            ],
            'website' => 'https://milestoneit.net',
            'primary_color' => '#667eea',
            'secondary_color' => '#764ba2',
            'bot_name' => 'Milestone Assistant',
            'bot_avatar' => '🤖',
            'ai_provider' => 'groq',
            'ai_model' => 'llama-3.1-8b-instant',
            'ai_temperature' => 0.7,
            'ai_max_tokens' => 300,
            'notification_email' => 'info@milestoneit.net',
            'is_active' => true
        ]);
        
        echo "✅ Company created: {$company->name}\n\n";

        // ============================================
        // 2. Create Products
        // ============================================
        $products = [
            [
                'name' => 'ePlus Accounting Software',
                'slug' => 'eplus-accounting',
                'short_description' => 'ePlus is an accounting and inventory management software that helps you manage daily business transactions smoothly.',
                'full_description' => 'ePlus is a comprehensive accounting and inventory management solution. It includes accounts & inventory management, automatic debit and credit entries, financial reports, and a simple and easy interface.',
                'keywords' => ['eplus', 'accounting', 'inventory', 'finance', 'accounts'],
                'demo_available' => true,
                'icon' => '📊',
                'display_order' => 1,
                'is_featured' => true,
                'is_active' => true
            ],
            [
                'name' => 'Project Management & Accounting Software',
                'slug' => 'project-management',
                'short_description' => 'This one is great for managing projects, tasks, expenses, inventory, and accounts in a single system.',
                'full_description' => 'A comprehensive project management solution that helps you track progress, manage costs, and keep your records updated without switching tools.',
                'keywords' => ['project', 'management', 'task', 'accounting', 'project management'],
                'demo_available' => true,
                'icon' => '📋',
                'display_order' => 2,
                'is_featured' => true,
                'is_active' => true
            ],
            [
                'name' => 'Payroll Software',
                'slug' => 'payroll',
                'short_description' => 'Our payroll software handles salary processing, working hours, tax calculations, and deductions, all in one place.',
                'full_description' => 'Complete payroll management system that automates salary processing, tracks working hours, handles tax calculations, and manages all deductions efficiently.',
                'keywords' => ['payroll', 'salary', 'hr', 'human resources', 'tax'],
                'demo_available' => true,
                'icon' => '💰',
                'display_order' => 3,
                'is_featured' => true,
                'is_active' => true
            ],
            [
                'name' => 'Fuel Meter Software',
                'slug' => 'fuel-meter',
                'short_description' => 'It\'s designed for fuel stations to easily track pump readings, daily fuel sales, and consumption with accuracy.',
                'full_description' => 'Specialized software for fuel stations that tracks pump readings, monitors daily fuel sales, and provides accurate consumption reports.',
                'keywords' => ['fuel', 'meter', 'petrol', 'diesel', 'pump', 'fuel station'],
                'demo_available' => true,
                'icon' => '⛽',
                'display_order' => 4,
                'is_active' => true
            ],
            [
                'name' => 'Protasker - Task Management',
                'slug' => 'protasker',
                'short_description' => 'Protasker helps teams organize tasks, deadlines, meetings, and responsibilities in one place, so nothing gets missed.',
                'full_description' => 'A powerful task management tool that helps teams stay organized with task tracking, deadline management, meeting scheduling, and responsibility assignment.',
                'keywords' => ['protasker', 'task', 'management', 'team', 'collaboration', 'productivity'],
                'demo_available' => true,
                'icon' => '✅',
                'display_order' => 5,
                'is_active' => true
            ]
        ];

        foreach ($products as $productData) {
            Product::create(array_merge($productData, ['company_id' => $company->id]));
        }
        
        echo "✅ Products created: " . count($products) . " products\n\n";

        // ============================================
        // 3. Create Services
        // ============================================
        $services = [
            [
                'name' => 'Website Development',
                'slug' => 'website-development',
                'short_description' => 'We design and develop professional, responsive, and easy-to-use websites that work smoothly on all devices.',
                'full_description' => 'Professional website development services including responsive design, modern UI/UX, and cross-device compatibility.',
                'keywords' => ['website', 'web', 'development', 'design', 'responsive'],
                'enquiry_available' => true,
                'icon' => '🌐',
                'display_order' => 1,
                'is_active' => true
            ],
            [
                'name' => 'Custom Software Development',
                'slug' => 'custom-software',
                'short_description' => 'We build custom software solutions based on how your business works—secure, scalable, and easy to use.',
                'full_description' => 'Tailored software development services that match your exact business requirements with focus on security, scalability, and usability.',
                'keywords' => ['software', 'custom', 'development', 'application', 'system'],
                'enquiry_available' => true,
                'icon' => '💻',
                'display_order' => 2,
                'is_active' => true
            ],
            [
                'name' => 'Custom Business Solutions',
                'slug' => 'custom-solutions',
                'short_description' => 'We create tailor-made business applications and system integrations to match your exact needs.',
                'full_description' => 'Custom business solutions including application development, system integrations, and workflow automation tailored to your business processes.',
                'keywords' => ['custom', 'business', 'solution', 'integration', 'automation'],
                'enquiry_available' => true,
                'icon' => '⚙️',
                'display_order' => 3,
                'is_active' => true
            ],
            [
                'name' => 'Digital Marketing',
                'slug' => 'digital-marketing',
                'short_description' => 'We help businesses improve their online presence, reach the right audience, and generate leads through digital marketing.',
                'full_description' => 'Comprehensive digital marketing services including SEO, SEM, content marketing, and lead generation strategies.',
                'keywords' => ['digital', 'marketing', 'seo', 'online', 'advertising', 'leads'],
                'enquiry_available' => true,
                'icon' => '📈',
                'display_order' => 4,
                'is_active' => true
            ],
            [
                'name' => 'Social Media Management',
                'slug' => 'social-media',
                'short_description' => 'We manage social media with content creation, posting, and campaign execution to keep your brand active and visible.',
                'full_description' => 'Complete social media management including content creation, scheduling, community management, and campaign execution across all major platforms.',
                'keywords' => ['social', 'media', 'facebook', 'instagram', 'content', 'campaign'],
                'enquiry_available' => true,
                'icon' => '📱',
                'display_order' => 5,
                'is_active' => true
            ]
        ];

        foreach ($services as $serviceData) {
            Service::create(array_merge($serviceData, ['company_id' => $company->id]));
        }
        
        echo "✅ Services created: " . count($services) . " services\n\n";

        // ============================================
        // 4. Create Chatbot Configs
        // ============================================
        $configs = [
            [
                'config_key' => 'welcome_message',
                'config_value' => "Hi there! 👋\n\nWelcome to Milestone Innovative Technologies.\n\nWe help businesses with software products, custom development, websites, and digital marketing.\n\nHow can I help you today?\n👉 You can ask me about our products, services, or request a demo.",
                'category' => 'messages'
            ],
            [
                'config_key' => 'goodbye_message',
                'config_value' => "Thank you for contacting us! 😊\n\nFeel free to return anytime.\nHave a great day!",
                'category' => 'messages'
            ],
            [
                'config_key' => 'error_message',
                'config_value' => "Sorry, there was an error 😔\n\nPlease contact us directly.",
                'category' => 'messages'
            ],
            [
                'config_key' => 'demo_success_message',
                'config_value' => "Perfect! 😊\n\n✅ Your demo request has been received!\n\nOur team will contact you shortly to schedule the demo.",
                'category' => 'messages'
            ],
            [
                'config_key' => 'enquiry_success_message',
                'config_value' => "Perfect! ✅\n\nYour requirement has been submitted.\n\nOur team will review it and get back to you soon.\n\nThank you for your interest! 😊",
                'category' => 'messages'
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
            [
                'template_name' => 'main_menu',
                'context' => 'default',
                'buttons' => [
                    ['label' => '📦 Our Products', 'value' => 'Products', 'order' => 1],
                    ['label' => '🛠 Our Services', 'value' => 'Services', 'order' => 2],
                    ['label' => '🎯 Request Demo', 'value' => 'Demo', 'order' => 3],
                    ['label' => '☎ Contact', 'value' => 'how can I contact you', 'order' => 4]
                ],
                'priority' => 10,
                'is_active' => true
            ],
            [
                'template_name' => 'products_context',
                'context' => 'products',
                'buttons' => [
                    ['label' => '🎯 Request Demo', 'value' => 'I want a demo', 'order' => 1],
                    ['label' => '🛠 View Services', 'value' => 'what services do you provide', 'order' => 2],
                    ['label' => '☎ Contact Us', 'value' => 'how can I contact you', 'order' => 3]
                ],
                'priority' => 9,
                'is_active' => true
            ],
            [
                'template_name' => 'services_context',
                'context' => 'services',
                'buttons' => [
                    ['label' => '📝 Share Requirement', 'value' => 'I have a requirement', 'order' => 1],
                    ['label' => '📦 View Products', 'value' => 'what products do you offer', 'order' => 2],
                    ['label' => '☎ Contact Us', 'value' => 'how can I contact you', 'order' => 3]
                ],
                'priority' => 9,
                'is_active' => true
            ],
            [
                'template_name' => 'contact_context',
                'context' => 'contact',
                'buttons' => [
                    ['label' => '📦 View Products', 'value' => 'what products do you offer', 'order' => 1],
                    ['label' => '🛠 View Services', 'value' => 'what services do you provide', 'order' => 2],
                    ['label' => '🎯 Request Demo', 'value' => 'I want a demo', 'order' => 3]
                ],
                'priority' => 8,
                'is_active' => true
            ],
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

        echo "🎉 Milestone IT data seeded successfully!\n";
        echo "📊 Summary:\n";
        echo "   - Company: {$company->name}\n";
        echo "   - Products: " . count($products) . "\n";
        echo "   - Services: " . count($services) . "\n";
        echo "   - Configs: " . count($configs) . "\n";
        echo "   - Button Templates: " . count($buttonTemplates) . "\n\n";
    }
}