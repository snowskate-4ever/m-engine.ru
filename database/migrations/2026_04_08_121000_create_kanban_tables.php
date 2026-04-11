<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kanban_boards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('kanban_columns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kanban_board_id')->constrained('kanban_boards')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('position')->default(0);
            $table->string('visibility_mode', 24)->default('inherit');
            $table->foreignId('visibility_set_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('kanban_cards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kanban_column_id')->constrained('kanban_columns')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('importance', 32)->default('normal');
            $table->unsignedInteger('position')->default(0);
            $table->foreignId('source_chat_message_id')->nullable()->constrained('messages')->nullOnDelete();
            $table->timestampTz('due_at')->nullable();
            $table->string('visibility_mode', 24)->default('inherit');
            $table->foreignId('visibility_set_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('kanban_board_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kanban_board_id')->constrained('kanban_boards')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('access_level', 16)->default('editor');
            $table->timestamps();

            $table->unique(['kanban_board_id', 'user_id']);
        });

        Schema::create('kanban_access_grants', function (Blueprint $table): void {
            $table->id();
            $table->morphs('subject');
            $table->morphs('grantee');
            $table->string('access_level', 16);
            $table->timestamps();

            $table->unique(
                ['subject_type', 'subject_id', 'grantee_type', 'grantee_id'],
                'kanban_access_grants_subject_grantee_unique',
            );
        });

        Schema::create('kanban_activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('action', 64);
            $table->unsignedBigInteger('kanban_board_id')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['created_at']);
            $table->index(['kanban_board_id', 'created_at']);
        });

        Schema::create('kanban_card_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kanban_card_id')->constrained('kanban_cards')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['kanban_card_id', 'created_at']);
        });

        Schema::create('kanban_card_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kanban_card_id')->constrained('kanban_cards')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('original_name');
            $table->string('path');
            $table->string('disk', 32)->default('local');
            $table->string('mime_type', 127)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();
        });

        Schema::create('kanban_user_shared_board_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kanban_board_id')->constrained('kanban_boards')->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->timestamps();

            $table->unique(['user_id', 'kanban_board_id']);
        });

        Schema::create('user_kanban_calendar_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('show_card_created_events')->default(true);
            $table->boolean('show_due_events')->default(true);
            $table->boolean('show_column_move_events')->default(true);
            $table->boolean('column_moves_include_all_targets')->default(true);
            $table->json('column_move_target_ids')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_kanban_calendar_settings');
        Schema::dropIfExists('kanban_user_shared_board_orders');
        Schema::dropIfExists('kanban_card_attachments');
        Schema::dropIfExists('kanban_card_comments');
        Schema::dropIfExists('kanban_activity_logs');
        Schema::dropIfExists('kanban_access_grants');
        Schema::dropIfExists('kanban_board_user');
        Schema::dropIfExists('kanban_cards');
        Schema::dropIfExists('kanban_columns');
        Schema::dropIfExists('kanban_boards');
    }
};
