<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizer_performer_invites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organizer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('peformer_id')->constrained('peformers')->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->foreignId('search_request_id')->nullable()->constrained('search_requests')->nullOnDelete();
            $table->foreignId('invited_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default('pending');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['organizer_user_id', 'status']);
            $table->index(['peformer_id', 'status']);
        });

        Schema::create('organizer_venue_invites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organizer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('concert_venue_id')->constrained('concert_venues')->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->foreignId('search_request_id')->nullable()->constrained('search_requests')->nullOnDelete();
            $table->foreignId('invited_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('proposed_start_at')->nullable();
            $table->dateTime('proposed_end_at')->nullable();
            $table->string('status', 32)->default('pending');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['organizer_user_id', 'status']);
            $table->index(['concert_venue_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizer_venue_invites');
        Schema::dropIfExists('organizer_performer_invites');
    }
};
