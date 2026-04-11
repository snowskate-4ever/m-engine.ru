<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_invites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->text('token_encrypted');
            $table->boolean('is_active')->default(true);
            $table->timestamp('used_at')->nullable();
            $table->foreignId('used_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['created_by_user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_invites');
    }
};
