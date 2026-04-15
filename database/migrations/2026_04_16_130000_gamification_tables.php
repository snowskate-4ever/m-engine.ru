<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achievements', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('xp_reward')->default(0);
            $table->json('criteria')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('user_achievements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('achievement_id')->constrained('achievements')->cascadeOnDelete();
            $table->timestampTz('unlocked_at')->useCurrent();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'achievement_id']);
        });

        Schema::create('user_xp_ledgers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->integer('delta');
            $table->string('reason', 128)->index();
            $table->nullableMorphs('context');
            $table->json('meta')->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_xp_ledgers');
        Schema::dropIfExists('user_achievements');
        Schema::dropIfExists('achievements');
    }
};
