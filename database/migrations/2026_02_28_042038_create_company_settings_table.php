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
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
             $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // ─── Mail / SMTP ───────────────────────────────────────────
            $table->string('mail_host')->nullable()->default('smtp.hostinger.com');
            $table->integer('mail_port')->nullable()->default(587);
            $table->string('mail_username')->nullable();
            $table->text('mail_password')->nullable();          // stored encrypted
            $table->string('mail_encryption')->nullable()->default('tls');
            $table->string('mail_from_address')->nullable();
            $table->string('mail_from_name')->nullable();

            // ─── Telegram ──────────────────────────────────────────────
            $table->text('telegram_bot_token')->nullable();     // stored encrypted

            // ─── AI API Keys ───────────────────────────────────────────
            $table->text('groq_api_key')->nullable();           // stored encrypted
            $table->text('openai_api_key')->nullable();         // stored encrypted
            $table->text('anthropic_api_key')->nullable();      // stored encrypted

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
