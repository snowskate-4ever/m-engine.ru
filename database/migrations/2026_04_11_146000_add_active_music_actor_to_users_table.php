<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'active_music_actor_type')) {
                $table->string('active_music_actor_type')->nullable();
            }
            if (! Schema::hasColumn('users', 'active_music_actor_id')) {
                $table->unsignedBigInteger('active_music_actor_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'active_music_actor_type')) {
                $table->dropColumn('active_music_actor_type');
            }
            if (Schema::hasColumn('users', 'active_music_actor_id')) {
                $table->dropColumn('active_music_actor_id');
            }
        });
    }
};
