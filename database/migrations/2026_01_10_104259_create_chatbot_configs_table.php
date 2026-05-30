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
        Schema::create('chatbot_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');

            // ✅ Reduced length from 255 to 100
            $table->string('config_key', 100);
            $table->text('config_value');
            $table->string('category', 50)->nullable();

            $table->timestamps();

            // ✅ This will now work (bigint + varchar(100) = within 767 bytes)
            $table->unique(['company_id', 'config_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatbot_configs');
    }
};
