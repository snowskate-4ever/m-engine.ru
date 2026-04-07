<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_tool_invocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tool_name', 64);
            $table->string('arguments_hash', 64);
            $table->boolean('ok')->default(false);
            $table->text('error_message')->nullable();
            $table->timestampTz('created_at');

            $table->index(['user_id', 'created_at']);
            $table->index('tool_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_tool_invocations');
    }
};
