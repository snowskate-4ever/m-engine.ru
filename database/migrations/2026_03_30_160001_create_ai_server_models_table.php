<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_server_models', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignId('ai_provider_id')->constrained('ai_providers')->cascadeOnDelete();
            $table->string('vendor_model_id');
            $table->string('display_name');
            $table->decimal('internal_cost_per_1k_prompt_tokens', 15, 6)->nullable();
            $table->decimal('internal_cost_per_1k_completion_tokens', 15, 6)->nullable();
            $table->decimal('estimated_cost_per_request', 15, 6)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->index(['is_active', 'sort_order']);
            $table->index('ai_provider_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_server_models');
    }
};
