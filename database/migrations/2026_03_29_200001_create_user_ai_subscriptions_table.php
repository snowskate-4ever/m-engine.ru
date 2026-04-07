<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_ai_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_subscription_tier_id')->constrained()->restrictOnDelete();
            $table->string('status', 24)->default('active');
            $table->timestampTz('current_period_start')->nullable();
            $table->timestampTz('current_period_end')->nullable();
            $table->string('payment_provider', 64)->nullable();
            $table->string('external_payment_ref', 191)->nullable();
            $table->boolean('cancel_at_period_end')->default(false);
            $table->timestampsTz();

            $table->index(['user_id', 'status']);
            $table->index(['current_period_end', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_ai_subscriptions');
    }
};
