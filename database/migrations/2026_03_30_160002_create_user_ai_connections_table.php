<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_ai_connections', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('driver', 64);
            $table->text('credentials');
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_used_at')->nullable();

            $table->index(['user_id', 'enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_ai_connections');
    }
};
