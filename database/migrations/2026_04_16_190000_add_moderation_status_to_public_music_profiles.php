<?php

declare(strict_types=1);

use App\Enums\ModerationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private const TABLES = [
        'musicians',
        'teachers',
        'peformers',
        'studios',
        'rehearsals',
        'concert_venues',
        'schools',
        'record_labels',
        'producer_centers',
        'shops',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $tbl) {
            if (! Schema::hasTable($tbl) || Schema::hasColumn($tbl, 'moderation_status')) {
                continue;
            }
            Schema::table($tbl, function (Blueprint $table): void {
                $table->string('moderation_status', 32)
                    ->default(ModerationStatus::Approved->value)
                    ->index();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $tbl) {
            if (! Schema::hasTable($tbl) || ! Schema::hasColumn($tbl, 'moderation_status')) {
                continue;
            }
            Schema::table($tbl, function (Blueprint $table): void {
                $table->dropColumn('moderation_status');
            });
        }
    }
};
