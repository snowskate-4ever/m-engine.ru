<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kanban_boards', function (Blueprint $table): void {
            if (! Schema::hasColumn('kanban_boards', 'source_type')) {
                $table->string('source_type')->nullable()->after('position');
            }
            if (! Schema::hasColumn('kanban_boards', 'source_id')) {
                $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            }
            $table->index(['source_type', 'source_id'], 'kanban_boards_source_idx');
        });
    }

    public function down(): void
    {
        Schema::table('kanban_boards', function (Blueprint $table): void {
            if (Schema::hasColumn('kanban_boards', 'source_type')) {
                $table->dropIndex('kanban_boards_source_idx');
                $table->dropColumn(['source_type', 'source_id']);
            }
        });
    }
};
