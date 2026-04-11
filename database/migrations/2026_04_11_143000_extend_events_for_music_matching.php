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
            if (! Schema::hasColumn('events', 'music_organizer_user_id')) {
                $table->foreignId('music_organizer_user_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('events', 'concert_venue_id')) {
                $table->foreignId('concert_venue_id')->nullable()->constrained('concert_venues')->nullOnDelete();
            }
            if (! Schema::hasColumn('events', 'assembly_status')) {
                $table->string('assembly_status', 32)->default('incomplete');
            }
        });

        Schema::create('event_peformer', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('peformer_id')->constrained('peformers')->cascadeOnDelete();
            $table->foreignId('added_via_search_request_id')->nullable()->constrained('search_requests')->nullOnDelete();
            $table->timestamps();

            $table->unique(['event_id', 'peformer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_peformer');

        Schema::table('events', function (Blueprint $table): void {
            foreach (['music_organizer_user_id', 'concert_venue_id'] as $column) {
                if (Schema::hasColumn('events', $column)) {
                    try {
                        $table->dropForeign([$column]);
                    } catch (\Throwable) {
                    }
                    $table->dropColumn($column);
                }
            }
            if (Schema::hasColumn('events', 'assembly_status')) {
                $table->dropColumn('assembly_status');
            }
        });
    }
};
