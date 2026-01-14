<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('musicians')) {
            // Для SQLite нужно пересоздать таблицу
            if (DB::getDriverName() === 'sqlite') {
                // Получаем список всех колонок кроме rating и price_per_hour
                $columns = Schema::getColumnListing('musicians');
                $columnsToKeep = array_filter($columns, fn($col) => !in_array($col, ['rating', 'price_per_hour']));
                
                // Создаем новую таблицу без rating и price_per_hour
                $columnDefs = [];
                foreach ($columnsToKeep as $col) {
                    if ($col === 'id') {
                        $columnDefs[] = 'id INTEGER PRIMARY KEY AUTOINCREMENT';
                    } elseif ($col === 'user_id') {
                        $columnDefs[] = 'user_id INTEGER';
                    } elseif ($col === 'active') {
                        $columnDefs[] = 'active INTEGER NOT NULL DEFAULT 1';
                    } elseif ($col === 'available_for_booking') {
                        $columnDefs[] = 'available_for_booking INTEGER NOT NULL DEFAULT 1';
                    } elseif ($col === 'is_session') {
                        $columnDefs[] = 'is_session INTEGER NOT NULL DEFAULT 0';
                    } elseif (in_array($col, ['created_at', 'updated_at', 'deleted_at'])) {
                        $columnDefs[] = $col . ' DATETIME';
                    } elseif (in_array($col, ['birth_date'])) {
                        $columnDefs[] = $col . ' DATE';
                    } else {
                        $columnDefs[] = $col . ' TEXT';
                    }
                }
                
                DB::statement('CREATE TABLE musicians_new (' . implode(', ', $columnDefs) . ')');
                
                // Копируем данные
                $columnsList = implode(', ', $columnsToKeep);
                DB::statement("INSERT INTO musicians_new ($columnsList) SELECT $columnsList FROM musicians");
                
                // Удаляем старую таблицу и переименовываем новую
                DB::statement('DROP TABLE musicians');
                DB::statement('ALTER TABLE musicians_new RENAME TO musicians');
                
                // Восстанавливаем индексы
                DB::statement('CREATE INDEX IF NOT EXISTS musicians_user_id_index ON musicians(user_id)');
                DB::statement('CREATE INDEX IF NOT EXISTS musicians_active_index ON musicians(active)');
                DB::statement('CREATE INDEX IF NOT EXISTS musicians_available_for_booking_index ON musicians(available_for_booking)');
            } else {
                // Для других БД используем стандартный способ
                Schema::table('musicians', function (Blueprint $table) {
                    if (Schema::hasColumn('musicians', 'rating')) {
                        $table->dropColumn('rating');
                    }
                    if (Schema::hasColumn('musicians', 'price_per_hour')) {
                        $table->dropColumn('price_per_hour');
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('musicians')) {
            Schema::table('musicians', function (Blueprint $table) {
                if (!Schema::hasColumn('musicians', 'rating')) {
                    $table->decimal('rating', 3, 2)->nullable()->default(0)->after('bio');
                }
                if (!Schema::hasColumn('musicians', 'price_per_hour')) {
                    $table->decimal('price_per_hour', 10, 2)->nullable()->after('rating');
                }
            });
        }
    }
};
