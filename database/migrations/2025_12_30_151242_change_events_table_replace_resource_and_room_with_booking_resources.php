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
        // Для SQLite используем более надежный подход
        if (\DB::getDriverName() === 'sqlite') {
            $this->migrateEventsTableForSQLite();
        } else {
            // Для других БД используем стандартный подход
            Schema::table('events', function (Blueprint $table) {
                $table->unsignedBigInteger('booking_resource_id')->nullable()->after('active');
                $table->dropColumn(['resource_id', 'room_id']);
            });
        }
    }

    private function migrateEventsTableForSQLite(): void
    {
        $events = \DB::table('events')->get();

        \DB::statement('DROP TABLE IF EXISTS events');

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->boolean('active');
            $table->unsignedBigInteger('booking_resource_id')->nullable();
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
            $table->softDeletes('deleted_at');
            $table->timestamps();
        });

        foreach ($events as $event) {
            \DB::table('events')->insert([
                'id' => $event->id,
                'name' => $event->name,
                'description' => $event->description,
                'active' => $event->active,
                'booking_resource_id' => $event->booking_resource_id ?? null,
                'start_at' => $event->start_at,
                'end_at' => $event->end_at,
                'deleted_at' => $event->deleted_at,
                'created_at' => $event->created_at,
                'updated_at' => $event->updated_at,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columns = Schema::getColumnListing('events');

        // Удаляем booking_resource_id если он есть
        if (in_array('booking_resource_id', $columns)) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropColumn('booking_resource_id');
            });
        }
    }
};
