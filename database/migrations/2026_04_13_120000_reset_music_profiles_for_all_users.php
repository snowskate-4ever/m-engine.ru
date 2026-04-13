<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->update([
            'music_profiles' => null,
        ]);

        DB::table('musicians')->update([
            'is_session' => false,
        ]);
    }

    public function down(): void
    {
        // Irreversible data reset: previous profile states are not recoverable.
    }
};
