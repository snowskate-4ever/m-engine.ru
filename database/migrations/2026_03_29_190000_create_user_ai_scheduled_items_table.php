<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_ai_scheduled_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 40);
            $table->string('title');
            $table->json('payload')->nullable();
            $table->timestampTz('next_fire_at');
            $table->text('repeat_rule')->nullable();
            $table->boolean('notify_push')->default(true);
            $table->boolean('notify_email')->default(false);
            $table->string('status', 24)->default('pending');
            $table->timestampsTz();

            $table->index(['status', 'next_fire_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_ai_scheduled_items');
    }
};
