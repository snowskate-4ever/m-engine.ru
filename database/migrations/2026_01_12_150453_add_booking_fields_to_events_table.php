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
        // For SQLite compatibility, recreate the table
        $this->recreateEventsTableWithoutBookingFields();
    }

    private function recreateEventsTableWithoutBookingFields(): void
    {
        // Get existing data
        $events = DB::table('events')->get();

        Schema::dropIfExists('events');

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            // Basic event fields
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('start_at');
            $table->dateTime('end_at')->nullable();
            $table->boolean('active')->default(true);

            // Resource and booking relation
            $table->foreignId('booked_resource_id')->constrained('resources')->onDelete('cascade');

            // Keep only the original fields, remove booking-specific ones
        });

        // Restore data (excluding the booking fields that are being removed)
        foreach ($events as $event) {
            DB::table('events')->insert([
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'start_at' => $event->start_at,
                'end_at' => $event->end_at,
                'active' => $event->active,
                'booked_resource_id' => $event->booked_resource_id,
                'created_at' => $event->created_at,
                'updated_at' => $event->updated_at,
            ]);
        }
    }
};
