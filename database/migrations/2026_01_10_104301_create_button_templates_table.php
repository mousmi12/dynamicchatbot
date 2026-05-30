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
        Schema::create('button_templates', function (Blueprint $table) {
            $table->id();
           $table->foreignId('company_id')->constrained()->onDelete('cascade');
            
            $table->string('template_name');
            $table->string('context');
            
            // ✅ Changed from json to text
            $table->text('buttons'); // Store as JSON string
            
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('button_templates');
    }
};
