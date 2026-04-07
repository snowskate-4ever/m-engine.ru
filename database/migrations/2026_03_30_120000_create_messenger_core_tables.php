<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->string('title')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('retention_days')->nullable();
            $table->unsignedBigInteger('ai_server_model_id')->nullable();
            $table->unsignedBigInteger('user_ai_connection_id')->nullable();
            $table->foreignId('direct_peer_min_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('direct_peer_max_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['direct_peer_min_id', 'direct_peer_max_id'], 'conversations_direct_peers_unique');
            $table->index('type');
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kind', 20);
            $table->text('body')->nullable();
            $table->boolean('is_forward')->default(false);
            $table->foreignId('forwarded_from_message_id')->nullable()->constrained('messages')->nullOnDelete();
            $table->json('forward_snapshot')->nullable();
            $table->uuid('client_message_id')->nullable()->unique();
            $table->timestamps();

            $table->index(['conversation_id', 'id']);
            $table->index(['conversation_id', 'created_at']);
        });

        Schema::create('conversation_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 20)->default('member');
            $table->foreignId('last_read_message_id')->nullable()->constrained('messages')->nullOnDelete();
            $table->timestamp('joined_at')->nullable();
            $table->boolean('notifications_muted')->default(false);
            $table->timestamp('mute_until')->nullable();
            $table->timestamps();

            $table->unique(['conversation_id', 'user_id']);
            $table->index('user_id');
        });

        Schema::create('message_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('messages')->cascadeOnDelete();
            $table->string('disk', 50);
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('checksum', 64)->nullable();
            $table->timestamps();

            $table->index('message_id');
        });

        Schema::create('messenger_user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->boolean('push_enabled')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messenger_user_preferences');
        Schema::dropIfExists('message_attachments');
        Schema::dropIfExists('conversation_user');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
    }
};
