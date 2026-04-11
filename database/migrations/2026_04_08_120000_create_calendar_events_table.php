<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_public')->default(false);
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->boolean('all_day')->default(false);
            $table->unsignedTinyInteger('reminder_minutes')->nullable();
            $table->string('color', 7)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'starts_at']);
            $table->index(['is_public', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
