<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (! Schema::hasColumn('users', 'music_profile_criteria')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->json('music_profile_criteria')->nullable()->after('music_profiles');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (Schema::hasColumn('users', 'music_profile_criteria')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('music_profile_criteria');
            });
        }
    }
};
