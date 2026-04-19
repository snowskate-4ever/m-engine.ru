<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_api_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('integration_api_token_id')->constrained('integration_api_tokens')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('method', 16);
            $table->string('path', 512);
            $table->unsignedSmallInteger('status_code');
            $table->unsignedInteger('duration_ms');
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['integration_api_token_id', 'created_at'], 'iaal_token_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_api_audit_logs');
    }
};
