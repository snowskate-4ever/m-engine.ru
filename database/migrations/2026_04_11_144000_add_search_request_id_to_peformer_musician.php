<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peformer_musician', function (Blueprint $table): void {
            if (! Schema::hasColumn('peformer_musician', 'search_request_id')) {
                $table->foreignId('search_request_id')->nullable()->after('invited_by_user_id')
                    ->constrained('search_requests')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('peformer_musician', function (Blueprint $table): void {
            if (Schema::hasColumn('peformer_musician', 'search_request_id')) {
                try {
                    $table->dropForeign(['search_request_id']);
                } catch (\Throwable) {
                }
                $table->dropColumn('search_request_id');
            }
        });
    }
};
