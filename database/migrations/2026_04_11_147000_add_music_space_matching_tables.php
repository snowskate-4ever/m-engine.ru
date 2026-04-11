<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            if (! Schema::hasColumn('events', 'matching_space_type')) {
                $table->string('matching_space_type')->nullable()->after('concert_venue_id');
            }
            if (! Schema::hasColumn('events', 'matching_space_id')) {
                $table->unsignedBigInteger('matching_space_id')->nullable()->after('matching_space_type');
                $table->index(['matching_space_type', 'matching_space_id'], 'events_matching_space_idx');
            }
            if (! Schema::hasColumn('events', 'matching_proposed_start_at')) {
                $table->dateTime('matching_proposed_start_at')->nullable()->after('end_at');
            }
            if (! Schema::hasColumn('events', 'matching_proposed_end_at')) {
                $table->dateTime('matching_proposed_end_at')->nullable()->after('matching_proposed_start_at');
            }
            if (! Schema::hasColumn('events', 'matching_booking_confirmed_at')) {
                $table->timestamp('matching_booking_confirmed_at')->nullable()->after('matching_proposed_end_at');
            }
        });

        Schema::create('organizer_studio_invites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organizer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('studio_id')->constrained('studios')->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->foreignId('search_request_id')->nullable()->constrained('search_requests')->nullOnDelete();
            $table->foreignId('invited_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('proposed_start_at')->nullable();
            $table->dateTime('proposed_end_at')->nullable();
            $table->string('status', 32)->default('pending');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['organizer_user_id', 'status']);
            $table->index(['studio_id', 'status']);
        });

        Schema::create('organizer_rehersal_invites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organizer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('rehersal_id')->constrained('rehearsals')->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->foreignId('search_request_id')->nullable()->constrained('search_requests')->nullOnDelete();
            $table->foreignId('invited_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('proposed_start_at')->nullable();
            $table->dateTime('proposed_end_at')->nullable();
            $table->string('status', 32)->default('pending');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['organizer_user_id', 'status']);
            $table->index(['rehersal_id', 'status']);
        });

        Schema::create('organizer_school_invites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organizer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->foreignId('search_request_id')->nullable()->constrained('search_requests')->nullOnDelete();
            $table->foreignId('invited_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('proposed_start_at')->nullable();
            $table->dateTime('proposed_end_at')->nullable();
            $table->string('status', 32)->default('pending');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['organizer_user_id', 'status']);
            $table->index(['school_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizer_school_invites');
        Schema::dropIfExists('organizer_rehersal_invites');
        Schema::dropIfExists('organizer_studio_invites');

        Schema::table('events', function (Blueprint $table): void {
            if (Schema::hasColumn('events', 'matching_space_id')) {
                try {
                    $table->dropIndex('events_matching_space_idx');
                } catch (\Throwable) {
                }
            }
            foreach ([
                'matching_space_type',
                'matching_space_id',
                'matching_proposed_start_at',
                'matching_proposed_end_at',
                'matching_booking_confirmed_at',
            ] as $column) {
                if (Schema::hasColumn('events', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
