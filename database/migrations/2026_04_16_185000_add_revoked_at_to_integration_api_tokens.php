<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('integration_api_tokens', function (Blueprint $table): void {
            $table->timestampTz('revoked_at')->nullable()->after('last_used_at');
            $table->index(['user_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::table('integration_api_tokens', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'revoked_at']);
            $table->dropColumn('revoked_at');
        });
    }
};
