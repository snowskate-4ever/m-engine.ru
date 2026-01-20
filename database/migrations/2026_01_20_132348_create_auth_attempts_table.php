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
        Schema::create('auth_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('channel')->nullable();
            $table->enum('channel_type', ['telegram', 'web', 'api', 'n8n_webhook', 'other'])->default('web');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('ip_address', 45)->nullable(); // IPv4/IPv6 support
            $table->text('user_agent')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->enum('status', ['pending', 'success', 'failed', 'expired'])->default('pending');
            $table->string('auth_token', 64)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('channel_type');
            $table->index('status');
            $table->index('user_id');
            $table->index('created_at');
            $table->unique('auth_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auth_attempts');
    }
};
