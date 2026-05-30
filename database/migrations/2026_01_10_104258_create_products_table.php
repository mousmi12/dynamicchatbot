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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            
            $table->string('name');
            $table->string('slug',191);
            $table->text('short_description');
            $table->text('full_description')->nullable();
            
            // ✅ Changed from json to text
            $table->text('keywords'); // Store as JSON string
            
            // Demo Configuration
            $table->boolean('demo_available')->default(false);
            $table->string('demo_url')->nullable();
            
            // Display Settings
            $table->string('icon')->nullable();
            $table->integer('display_order')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            
            // Pricing (optional)
            $table->decimal('price', 10, 2)->nullable();
            $table->string('price_display')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['company_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
