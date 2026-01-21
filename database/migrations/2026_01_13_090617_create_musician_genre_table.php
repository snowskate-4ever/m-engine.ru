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
        // Защита от ситуации, когда таблица уже существует (MySQL)
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('musician_genre');
        Schema::enableForeignKeyConstraints();

        Schema::create('musician_genre', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('musician_id');
            $table->unsignedBigInteger('genre_id');
            $table->integer('preference_level')->nullable()->default(5); // Уровень предпочтения от 1 до 10
            $table->boolean('is_primary')->default(false); // Основной жанр
            $table->timestamps();
            
            // Уникальность пары musician_id + genre_id
            $table->unique(['musician_id', 'genre_id']);
            $table->index('musician_id');
            $table->index('genre_id');
            $table->index('is_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('musician_genre');
    }
};
