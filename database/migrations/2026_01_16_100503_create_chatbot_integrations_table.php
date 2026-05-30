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
        Schema::create('chatbot_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');

            $table->string('integration_type'); // courier, pharmacy, ecommerce
            $table->boolean('enable_order_tracking')->default(false);
            $table->boolean('enable_medicine_info')->default(false);
            $table->boolean('enable_driver_info')->default(false);

            $table->text('features_config')->nullable(); // Changed from json to text
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatbot_integrations');
    }
};
