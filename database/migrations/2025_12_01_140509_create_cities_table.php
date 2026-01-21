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
        // Защита от ситуации, когда таблица уже существует (MySQL)
        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::statement('DROP TABLE IF EXISTS `cities`');
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } else {
            Schema::disableForeignKeyConstraints();
            Schema::dropIfExists('cities');
            Schema::enableForeignKeyConstraints();
        }

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->nullable()->constrained('regions')->onDelete('cascade');
            $table->foreignId('country_id')->constrained('countries')->onDelete('cascade');
            $table->string('name', 100);
            $table->string('name_eng', 100)->nullable(); // Английское название
            $table->string('slug', 120)->unique(); // URL-friendly имя
            $table->string('phone_code', 120)->nullable(); // URL-friendly имя
            $table->string('currency_code', 120)->nullable(); // URL-friendly имя
            $table->string('currency_symbol', 120)->nullable(); // URL-friendly имя
            $table->string('code', 50)->nullable(); 
            $table->string('timezone', 50)->nullable(); // Europe/Moscow
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->integer('population')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            // Индексы
            $table->index('name');
            $table->index('slug');
            $table->index('region_id');
            $table->index('country_id');
            $table->index('population');
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
