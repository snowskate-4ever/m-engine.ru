<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usage_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_server_model_id')->nullable()->constrained('ai_server_models')->nullOnDelete();
            $table->string('source', 16);
            $table->unsignedInteger('tokens_prompt')->default(0);
            $table->unsignedInteger('tokens_completion')->default(0);
            $table->decimal('estimated_internal_cost', 14, 6)->nullable();
            $table->foreignId('conversation_id')->nullable()->constrained('conversations')->nullOnDelete();
            $table->timestampTz('created_at');

            $table->index(['user_id', 'created_at']);
            $table->index('ai_server_model_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_ledger');
    }
};
