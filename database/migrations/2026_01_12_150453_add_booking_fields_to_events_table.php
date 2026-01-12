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
        Schema::table('events', function (Blueprint $table) {
            // Связь с комнатой (опционально) - для бронирования конкретной комнаты
            $table->unsignedBigInteger('room_id')->nullable()->after('booked_resource_id');
            $table->foreign('room_id')->references('id')->on('rooms')->onDelete('set null');
            
            // Связь с пользователем, который создал бронирование
            $table->unsignedBigInteger('user_id')->nullable()->after('room_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            
            // Статус бронирования
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])
                  ->default('pending')
                  ->after('active');
            
            // Дополнительные поля для бронирования
            $table->text('notes')->nullable()->after('end_at'); // Примечания к бронированию
            $table->decimal('price', 10, 2)->nullable()->after('notes'); // Цена бронирования
            
            // Индексы для производительности при проверке пересечений
            $table->index(['booked_resource_id', 'start_at', 'end_at']);
            $table->index(['room_id', 'start_at', 'end_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Удаляем индексы
            $table->dropIndex(['booked_resource_id', 'start_at', 'end_at']);
            $table->dropIndex(['room_id', 'start_at', 'end_at']);
            
            // Удаляем внешние ключи
            $table->dropForeign(['room_id']);
            $table->dropForeign(['user_id']);
            
            // Удаляем колонки
            $table->dropColumn([
                'room_id',
                'user_id',
                'status',
                'notes',
                'price',
            ]);
        });
    }
};
