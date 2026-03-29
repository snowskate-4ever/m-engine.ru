<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_request_logs', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained('conversations')->nullOnDelete();

            $table->string('source', 16);
            $table->unsignedBigInteger('ai_server_model_id')->nullable();
            $table->unsignedBigInteger('user_ai_connection_id')->nullable();

            $table->unsignedInteger('duration_ms')->default(0);
            $table->string('status', 16);
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('provider_error_code', 128)->nullable();
            $table->text('error_message')->nullable();

            $table->unsignedInteger('tokens_prompt')->default(0);
            $table->unsignedInteger('tokens_completion')->default(0);
            $table->decimal('estimated_internal_cost', 15, 6)->nullable();

            $table->string('provider_request_id', 128)->nullable();
            $table->text('prompt_excerpt')->nullable();
            $table->text('response_excerpt')->nullable();

            $table->index(['user_id', 'created_at']);
            $table->index('status');
            $table->index('ai_server_model_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_request_logs');
    }
};
