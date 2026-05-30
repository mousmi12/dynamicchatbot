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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
           $table->foreignId('company_id')->constrained()->onDelete('cascade');
            
            $table->string('name');
            $table->string('slug');
            $table->text('short_description');
            $table->text('full_description')->nullable();
            
            // ✅ Changed from json to text
            $table->text('keywords'); // Store as JSON string
            
            $table->boolean('enquiry_available')->default(true);
            
            $table->string('icon')->nullable();
            $table->integer('display_order')->default(0);
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
        Schema::dropIfExists('services');
    }
};
