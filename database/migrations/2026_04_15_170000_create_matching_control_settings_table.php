<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matching_control_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('interval_minutes')->default(60);
            $table->string('default_scope', 32)->default('all');
            $table->string('provider', 64)->default('openai');
            $table->string('model', 128)->default('gpt-4o-mini');
            $table->decimal('score_threshold', 5, 4)->default(0.6500);
            $table->json('weights')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matching_control_settings');
    }
};
