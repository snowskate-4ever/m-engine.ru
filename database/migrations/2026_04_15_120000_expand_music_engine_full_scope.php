<?php

declare(strict_types=1);

use App\Enums\AdStatus;
use App\Enums\ModerationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('search_requests', function (Blueprint $table): void {
            if (! Schema::hasColumn('search_requests', 'ad_status')) {
                $table->string('ad_status', 32)->default(AdStatus::Draft->value)->index();
            }
            if (! Schema::hasColumn('search_requests', 'moderation_status')) {
                $table->string('moderation_status', 32)->default(ModerationStatus::Pending->value)->index();
            }
            if (! Schema::hasColumn('search_requests', 'city_id')) {
                $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            }
            if (! Schema::hasColumn('search_requests', 'target_kind')) {
                $table->string('target_kind', 64)->nullable()->index();
            }
            if (! Schema::hasColumn('search_requests', 'my_city_only')) {
                $table->boolean('my_city_only')->default(false);
            }
            if (! Schema::hasColumn('search_requests', 'description')) {
                $table->text('description')->nullable();
            }
            if (! Schema::hasColumn('search_requests', 'published_at')) {
                $table->timestamp('published_at')->nullable();
            }
        });

        Schema::create('search_request_responses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('search_request_id')->constrained('search_requests')->cascadeOnDelete();
            $table->foreignId('responder_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('message')->nullable();
            $table->string('status', 32)->default('pending')->index();
            $table->timestamp('contact_unlocked_at')->nullable();
            $table->timestamps();

            $table->unique(['search_request_id', 'responder_user_id'], 'search_request_response_unique');
        });

        Schema::table('bookings', function (Blueprint $table): void {
            if (! Schema::hasColumn('bookings', 'bookable_type')) {
                $table->string('bookable_type')->nullable();
            }
            if (! Schema::hasColumn('bookings', 'bookable_id')) {
                $table->unsignedBigInteger('bookable_id')->nullable();
                $table->index(['bookable_type', 'bookable_id']);
            }
            if (! Schema::hasColumn('bookings', 'booked_by_user_id')) {
                $table->foreignId('booked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('bookings', 'search_request_id')) {
                $table->foreignId('search_request_id')->nullable()->constrained('search_requests')->nullOnDelete();
            }
            if (! Schema::hasColumn('bookings', 'status')) {
                $table->string('status', 32)->default('planned')->index();
            }
            if (! Schema::hasColumn('bookings', 'timezone')) {
                $table->string('timezone', 64)->nullable();
            }
            if (! Schema::hasColumn('bookings', 'starts_at')) {
                $table->timestampTz('starts_at')->nullable()->index();
            }
            if (! Schema::hasColumn('bookings', 'ends_at')) {
                $table->timestampTz('ends_at')->nullable()->index();
            }
        });

        Schema::table('calendar_events', function (Blueprint $table): void {
            if (! Schema::hasColumn('calendar_events', 'source_type')) {
                $table->string('source_type')->nullable();
            }
            if (! Schema::hasColumn('calendar_events', 'source_id')) {
                $table->unsignedBigInteger('source_id')->nullable();
                $table->index(['source_type', 'source_id']);
            }
            if (! Schema::hasColumn('calendar_events', 'status')) {
                $table->string('status', 32)->default('planned')->index();
            }
            if (! Schema::hasColumn('calendar_events', 'timezone')) {
                $table->string('timezone', 64)->nullable();
            }
        });

        Schema::table('kanban_cards', function (Blueprint $table): void {
            if (! Schema::hasColumn('kanban_cards', 'source_type')) {
                $table->string('source_type')->nullable();
            }
            if (! Schema::hasColumn('kanban_cards', 'source_id')) {
                $table->unsignedBigInteger('source_id')->nullable();
                $table->index(['source_type', 'source_id']);
            }
            if (! Schema::hasColumn('kanban_cards', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->index();
            }
        });

        Schema::create('automation_preset_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('owner_type')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->string('preset_type', 64)->index();
            $table->boolean('is_enabled')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['owner_type', 'owner_id']);
        });

        Schema::create('automation_rule_executions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('automation_preset_setting_id')->nullable()->constrained('automation_preset_settings')->nullOnDelete();
            $table->string('trigger_event', 128)->index();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->boolean('is_success')->default(true);
            $table->json('payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::create('reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('author_user_id')->constrained('users')->cascadeOnDelete();
            $table->morphs('reviewable');
            $table->morphs('contextable');
            $table->unsignedTinyInteger('rating');
            $table->text('body')->nullable();
            $table->string('moderation_status', 32)->default(ModerationStatus::Pending->value)->index();
            $table->timestamps();
        });

        Schema::create('review_replies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('review_id')->constrained('reviews')->cascadeOnDelete();
            $table->foreignId('author_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->unique('review_id');
        });

        Schema::create('matching_run_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('run_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_automatic')->default(true)->index();
            $table->string('scope', 64)->default('all');
            $table->unsignedInteger('processed_count')->default(0);
            $table->unsignedInteger('matched_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->json('meta')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matching_run_logs');
        Schema::dropIfExists('review_replies');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('automation_rule_executions');
        Schema::dropIfExists('automation_preset_settings');
        Schema::dropIfExists('search_request_responses');
    }
};
