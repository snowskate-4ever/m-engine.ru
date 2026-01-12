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
        // SQLite не поддерживает удаление колонок напрямую, используем пересоздание таблицы
        if (DB::getDriverName() === 'sqlite') {
            // Удаляем временную таблицу, если она существует
            DB::statement('DROP TABLE IF EXISTS events_backup');
            
            // Удаляем индексы, если они существуют
            DB::statement('DROP INDEX IF EXISTS events_name_unique');
            
            // Сохраняем данные во временную таблицу
            DB::statement('CREATE TABLE events_backup AS SELECT id, name, description, active, start_at, end_at, deleted_at, created_at, updated_at FROM events');
            
            // Удаляем старую таблицу и все её индексы
            DB::statement('DROP TABLE IF EXISTS events');
            
            // Создаем новую таблицу с правильной структурой
            Schema::create('events', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->text('description');
                $table->boolean('active');
                $table->unsignedBigInteger('booking_resource_id')->nullable();
                $table->unsignedBigInteger('booked_resource_id')->nullable();
                $table->dateTime('start_at')->nullable();
                $table->dateTime('end_at')->nullable();
                $table->softDeletes('deleted_at');
                $table->timestamps();
            });
            
            // Добавляем внешние ключи отдельно (SQLite требует специального синтаксиса)
            // Для SQLite внешние ключи нужно добавить через ALTER TABLE или включить их поддержку
            DB::statement('PRAGMA foreign_keys=ON');
            
            // Копируем данные из временной таблицы
            DB::statement('
                INSERT INTO events (id, name, description, active, start_at, end_at, deleted_at, created_at, updated_at, booking_resource_id, booked_resource_id)
                SELECT id, name, description, active, start_at, end_at, deleted_at, created_at, updated_at, NULL, NULL
                FROM events_backup
            ');
            
            // Удаляем временную таблицу
            DB::statement('DROP TABLE IF EXISTS events_backup');
        } else {
            // Для других БД (MySQL, PostgreSQL) используем стандартный подход
            Schema::table('events', function (Blueprint $table) {
                // Удаляем старые поля
                $table->dropColumn(['resource_id', 'room_id']);
                
                // Добавляем новые поля для ресурсов бронирования
                $table->unsignedBigInteger('booking_resource_id')->nullable()->after('active');
                $table->unsignedBigInteger('booked_resource_id')->nullable()->after('booking_resource_id');
                
                // Добавляем внешние ключи
                $table->foreign('booking_resource_id')->references('id')->on('resources')->onDelete('set null');
                $table->foreign('booked_resource_id')->references('id')->on('resources')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // Удаляем временную таблицу, если она существует
            DB::statement('DROP TABLE IF EXISTS events_backup');
            
            // Сохраняем данные во временную таблицу
            DB::statement('CREATE TABLE events_backup AS SELECT id, name, description, active, start_at, end_at, deleted_at, created_at, updated_at, booking_resource_id, booked_resource_id FROM events');
            
            // Удаляем новую таблицу
            DB::statement('DROP TABLE IF EXISTS events');
            
            // Восстанавливаем старую структуру
            Schema::create('events', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->text('description');
                $table->boolean('active');
                $table->uuid('resource_id')->nullable();
                $table->uuid('room_id')->nullable();
                $table->dateTime('start_at')->nullable();
                $table->dateTime('end_at')->nullable();
                $table->softDeletes('deleted_at');
                $table->timestamps();
            });
            
            // Копируем данные обратно
            DB::statement('
                INSERT INTO events (id, name, description, active, start_at, end_at, deleted_at, created_at, updated_at, resource_id, room_id)
                SELECT id, name, description, active, start_at, end_at, deleted_at, created_at, updated_at, NULL, NULL
                FROM events_backup
            ');
            
            // Удаляем временную таблицу
            DB::statement('DROP TABLE IF EXISTS events_backup');
        } else {
            Schema::table('events', function (Blueprint $table) {
                // Удаляем внешние ключи
                $table->dropForeign(['booking_resource_id']);
                $table->dropForeign(['booked_resource_id']);
                
                // Удаляем новые поля
                $table->dropColumn(['booking_resource_id', 'booked_resource_id']);
                
                // Возвращаем старые поля
                $table->uuid('resource_id')->nullable()->after('active');
                $table->uuid('room_id')->nullable()->after('resource_id');
            });
        }
    }
};
