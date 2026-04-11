<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('search_goal', 96);
            $table->string('status', 32)->default('draft');
            $table->string('initiator_type');
            $table->unsignedBigInteger('initiator_id');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('criteria')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->string('closure_reason', 64)->nullable();
            $table->json('fulfillment_context')->nullable();
            $table->timestamps();

            $table->index(['initiator_type', 'initiator_id']);
            $table->index(['status', 'search_goal']);
        });

        Schema::create('search_request_matches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('search_request_id')->constrained('search_requests')->cascadeOnDelete();
            $table->string('candidate_type');
            $table->unsignedBigInteger('candidate_id');
            $table->decimal('score', 8, 4)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['candidate_type', 'candidate_id']);
            $table->unique(['search_request_id', 'candidate_type', 'candidate_id'], 'search_request_matches_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_request_matches');
        Schema::dropIfExists('search_requests');
    }
};
