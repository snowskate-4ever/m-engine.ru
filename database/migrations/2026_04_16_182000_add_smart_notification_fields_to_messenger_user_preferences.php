<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messenger_user_preferences', function (Blueprint $table): void {
            $table->string('priority_mode', 20)->default('balanced')->after('push_enabled');
            $table->unsignedTinyInteger('quiet_hours_start')->nullable()->after('priority_mode');
            $table->unsignedTinyInteger('quiet_hours_end')->nullable()->after('quiet_hours_start');
        });
    }

    public function down(): void
    {
        Schema::table('messenger_user_preferences', function (Blueprint $table): void {
            $table->dropColumn(['priority_mode', 'quiet_hours_start', 'quiet_hours_end']);
        });
    }
};
