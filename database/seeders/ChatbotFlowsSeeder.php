<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChatbotFlowsSeeder extends Seeder
{
    public function run()
    {
        // Get company IDs
        $milestoneIT = DB::table('companies')->where('slug', 'milestone-it')->first();
        $swiftCourier = DB::table('companies')->where('slug', 'swift-courier')->first();

        if (!$milestoneIT || !$swiftCourier) {
            $this->command->error('Companies not found!');
            return;
        }

        // Clear existing flows
        DB::table('chatbot_flows')->whereIn('company_id', [$milestoneIT->id, $swiftCourier->id])->delete();

        // ============ MILESTONE IT FLOWS ============

        // Demo Request Flow
        DB::table('chatbot_flows')->insert([
            'company_id' => $milestoneIT->id,
            'flow_name' => 'demo_request',
            'flow_type' => 'form_collection',
            'triggers' => json_encode(['demo', 'i want a demo', 'request demo', 'show demo']),
            'steps' => json_encode([
                [
                    'message' => "Perfect! 😊\n\nLet me get your details for the demo.\n\n📝 What's your name?",
                    'cache_key' => 'demo_name',
                    'field_name' => 'customer_name'
                ],
                [
                    'message' => "Thanks! 👍\n\n📞 Your mobile number please:",
                    'cache_key' => 'demo_mobile',
                    'field_name' => 'mobile'
                ],
                [
                    'message' => "Great! 📦\n\nWhich product are you interested in?\n\nJust tell us what you need:",
                    'cache_key' => 'demo_product',
                    'field_name' => 'product'
                ]
            ]),
            'data_config' => json_encode([
                'save_to_table' => 'demo_requests',
                'send_email' => true,
                'success_message' => "Perfect! 😊\n\n✅ Your demo request has been received!\n\nOur team will contact you shortly to schedule the demo.\n\nIs there anything else I can help with?",
                'success_buttons' => 'contact'
            ]),
            'priority' => 10,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Service Enquiry Flow
        DB::table('chatbot_flows')->insert([
            'company_id' => $milestoneIT->id,
            'flow_name' => 'service_enquiry',
            'flow_type' => 'form_collection',
            'triggers' => json_encode(['requirement', 'i have a requirement', 'share requirement', 'enquiry']),
            'steps' => json_encode([
                [
                    'message' => "Great 👍\n\nLet's get your details so our team can help you.\n\n📝 First, what's your name?",
                    'cache_key' => 'service_name',
                    'field_name' => 'name'
                ],
                [
                    'message' => "Thanks! 😊\n\n📝 Which service are you interested in?",
                    'cache_key' => 'service_requirement',
                    'field_name' => 'requirement'
                ],
                [
                    'message' => "Got it! 👍\n\n📞 Please share your mobile number:",
                    'cache_key' => 'service_mobile',
                    'field_name' => 'mobile'
                ]
            ]),
            'data_config' => json_encode([
                'save_to_table' => 'service_enquiries',
                'send_email' => true,
                'success_message' => "Perfect! ✅\n\nYour requirement has been submitted.\n\nOur team will review it and get back to you soon.\n\nThank you for your interest! 😊",
                'success_buttons' => 'default'
            ]),
            'priority' => 9,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $this->command->info('✅ Milestone IT flows created!');

        // ============ SWIFT COURIER FLOWS ============

        // Track Order Flow
        DB::table('chatbot_flows')->insert([
            'company_id' => $swiftCourier->id,
            'flow_name' => 'track_order',
            'flow_type' => 'data_query',
            'triggers' => json_encode(['track', 'tracking', 'where is my order', 'order status']),
            'steps' => json_encode([
                [
                    'message' => "Sure! I can help you track your order. 📦\n\nPlease provide your tracking number.\n\nExample: ON#20251103#006",
                    'cache_key' => 'tracking_number',
                    'field_name' => 'order_number'
                ]
            ]),
            'data_config' => json_encode([
                'table' => 'orders',
                'search_columns' => ['order_ID', 'shop_order_ID'],
                'response_template' => "📦 Order Tracking Results\n\nOrder ID: {order_ID}\nStatus: {status}\n📍 Delivery To: {delivery_address}\n🕐 Last Update: {updated_at}\n\nNeed more help?",
                'not_found_message' => "Sorry, I couldn't find any order with that number. 😔\n\nPlease check:\n• The tracking number is correct\n• There are no extra spaces\n\nWould you like to try again?",
                'success_buttons' => 'tracking',
                'not_found_buttons' => 'tracking'
            ]),
            'priority' => 10,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Customer Support Flow
        DB::table('chatbot_flows')->insert([
            'company_id' => $swiftCourier->id,
            'flow_name' => 'customer_support',
            'flow_type' => 'form_collection',
            'triggers' => json_encode(['support', 'help', 'customer support', 'complaint', 'issue']),
            'steps' => json_encode([
                [
                    'message' => "I'm here to help! 💬\n\nPlease tell me your name:",
                    'cache_key' => 'support_name',
                    'field_name' => 'name'
                ],
                [
                    'message' => "Thanks! 👍\n\nPlease describe your issue or question:",
                    'cache_key' => 'support_issue',
                    'field_name' => 'requirement'
                ],
                [
                    'message' => "Got it! 📝\n\nPlease provide your phone number so our team can reach you:",
                    'cache_key' => 'support_contact',
                    'field_name' => 'mobile'
                ]
            ]),
            'data_config' => json_encode([
                'save_to_table' => 'service_enquiries',
                'send_email' => true,
                'success_message' => "✅ Support Ticket Created!\n\nOur team will contact you shortly.\n\nExpected response time: Within 2 hours\n\nIs there anything else I can help with?",
                'success_buttons' => 'support'
            ]),
            'priority' => 9,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $this->command->info('✅ Swift Courier flows created!');
        $this->command->info('🎉 All chatbot flows seeded successfully!');
    }
}