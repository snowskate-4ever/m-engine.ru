<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = ['musicians', 'teachers', 'places', 'rehearsals', 'studios', 'peformers'];
        
        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'description')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $table->text('description')->nullable()->change();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['musicians', 'teachers', 'places', 'rehearsals', 'studios', 'peformers'];
        
        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'description')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $table->text('description')->nullable(false)->change();
                });
            }
        }
    }
};
