<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug',191)->unique();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            
            // ✅ Changed from json to text (for older MySQL)
            $table->text('phone_numbers'); // Store as JSON string
            $table->text('email_addresses'); // Store as JSON string
            $table->string('website')->nullable();
            
            // Chatbot Configuration
            $table->string('primary_color')->default('#667eea');
            $table->string('secondary_color')->default('#764ba2');
            $table->string('bot_name')->default('Support Bot');
            $table->string('bot_avatar')->nullable();
            
            // AI Settings
            $table->string('ai_provider')->default('groq');
            $table->string('ai_model')->default('llama-3.1-8b-instant');
            $table->decimal('ai_temperature', 3, 1)->default(0.7); // ✅ Changed from 2,1 to 3,1
            $table->integer('ai_max_tokens')->default(300);
            
            // Email Notifications
            $table->string('notification_email');
            
            // Status
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
