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
        if (Schema::hasTable('resources') && (Schema::hasColumn('resources', 'name') || Schema::hasColumn('resources', 'description'))) {
            // Для SQLite нужно пересоздать таблицу
            if (DB::getDriverName() === 'sqlite') {
                // Создаем новую таблицу без name и description
                DB::statement('
                    CREATE TABLE resources_new (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        active INTEGER NOT NULL DEFAULT 1,
                        type_id INTEGER NOT NULL,
                        start_at DATE NOT NULL,
                        end_at DATE,
                        deleted_at DATETIME,
                        created_at DATETIME,
                        updated_at DATETIME
                    )
                ');
                
                // Копируем данные (исключая name и description)
                DB::statement('
                    INSERT INTO resources_new 
                    (id, active, type_id, start_at, end_at, deleted_at, created_at, updated_at)
                    SELECT 
                        id, active, type_id, start_at, end_at, deleted_at, created_at, updated_at
                    FROM resources
                ');
                
                // Удаляем старую таблицу и переименовываем новую
                DB::statement('DROP TABLE resources');
                DB::statement('ALTER TABLE resources_new RENAME TO resources');
                
                // Создаем индексы, если они нужны
                // (обычно Laravel создает их автоматически, но на всякий случай)
            } else {
                // Для других БД используем стандартный способ
                Schema::table('resources', function (Blueprint $table) {
                    if (Schema::hasColumn('resources', 'name')) {
                        $table->dropColumn('name');
                    }
                    if (Schema::hasColumn('resources', 'description')) {
                        $table->dropColumn('description');
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
        if (Schema::hasTable('resources')) {
            Schema::table('resources', function (Blueprint $table) {
                if (!Schema::hasColumn('resources', 'name')) {
                    $table->string('name')->nullable()->after('id');
                }
                if (!Schema::hasColumn('resources', 'description')) {
                    $table->text('description')->nullable()->after('name');
                }
            });
        }
    }
};
