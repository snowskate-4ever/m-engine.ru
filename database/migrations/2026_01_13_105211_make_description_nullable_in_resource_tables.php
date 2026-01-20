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
                // For SQLite, we need to recreate the table since CHANGE is not supported
                $this->recreateTableWithNotNullDescription($tableName);
            }
        }
    }

    private function recreateTableWithNotNullDescription(string $tableName): void
    {
        // Get the current table schema
        $columns = Schema::getColumnListing($tableName);
        $tableInfo = DB::select("PRAGMA table_info({$tableName})");

        // Create new table schema with description NOT NULL
        $createSql = $this->getCreateTableSql($tableName, $tableInfo);

        // Update any NULL descriptions to empty strings
        DB::table($tableName)->whereNull('description')->update(['description' => '']);

        DB::statement("PRAGMA foreign_keys = OFF");
        DB::statement("ALTER TABLE {$tableName} RENAME TO {$tableName}_old");
        DB::statement($createSql);

        // Copy data from old table to new table
        $columnList = implode(', ', $columns);
        DB::statement("INSERT INTO {$tableName} ({$columnList}) SELECT {$columnList} FROM {$tableName}_old");

        // Drop old table
        DB::statement("DROP TABLE {$tableName}_old");
        DB::statement("PRAGMA foreign_keys = ON");
    }

    private function getCreateTableSql(string $tableName, array $tableInfo): string
    {
        $columns = [];
        foreach ($tableInfo as $column) {
            $colName = $column->name;
            $colType = $column->type;
            $notNull = $column->notnull ? 'NOT NULL' : '';
            $default = $column->dflt_value ? "DEFAULT {$column->dflt_value}" : '';

            // Make description NOT NULL
            if ($colName === 'description') {
                $notNull = 'NOT NULL';
            }

            $columns[] = "{$colName} {$colType} {$notNull} {$default}";
        }

        $columnsSql = implode(', ', array_map('trim', array_filter($columns)));
        return "CREATE TABLE {$tableName} ({$columnsSql})";
    }
};
