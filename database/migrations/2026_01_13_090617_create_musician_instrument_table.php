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
        Schema::create('musician_instrument', function (Blueprint $table) {
            $table->id();
            $table->foreignId('musician_id')->constrained('musicians')->onDelete('cascade');
            $table->foreignId('instrument_id')->constrained('instruments')->onDelete('cascade');
            $table->integer('proficiency_level')->nullable()->default(5); // Уровень владения от 1 до 10
            $table->boolean('is_primary')->default(false); // Основной инструмент
            $table->timestamps();
            
            // Уникальность пары musician_id + instrument_id
            $table->unique(['musician_id', 'instrument_id']);
            $table->index('musician_id');
            $table->index('instrument_id');
            $table->index('is_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('musician_instrument');
    }
};
