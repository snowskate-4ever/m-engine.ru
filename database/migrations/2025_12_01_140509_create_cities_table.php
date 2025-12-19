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
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->nullable()->constrained('regions')->onDelete('cascade');
            $table->foreignId('country_id')->constrained('countries')->onDelete('cascade');
            $table->string('name', 100);
            $table->string('name_eng', 100)->nullable(); // Английское название
            $table->string('slug', 120)->unique(); // URL-friendly имя
            $table->string('timezone', 50)->nullable(); // Europe/Moscow
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->integer('population')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_capital')->default(false); // Столица региона/страны
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            // Индексы
            $table->index('name');
            $table->index('slug');
            $table->index('region_id');
            $table->index('country_id');
            $table->index('population');
            $table->index('is_capital');
            $table->index('is_active');
            $table->index('sort_order');
            $table->index(['country_id', 'region_id']);
            
            // Составной индекс для частых запросов
            $table->index(['country_id', 'region_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
