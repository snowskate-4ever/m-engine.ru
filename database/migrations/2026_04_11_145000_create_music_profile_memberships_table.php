<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('music_profile_memberships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->string('role', 32);
            $table->string('status', 32)->default('pending');
            $table->foreignId('invited_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id', 'status'], 'music_profile_memberships_entity_status_idx');
            $table->index(['member_user_id', 'status'], 'music_profile_memberships_member_status_idx');
            $table->unique(['member_user_id', 'entity_type', 'entity_id', 'role'], 'music_profile_memberships_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('music_profile_memberships');
    }
};
