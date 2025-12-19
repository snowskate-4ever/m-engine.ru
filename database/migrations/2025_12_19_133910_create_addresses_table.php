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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            
            // Полиморфная связь (для пользователей, компаний, складов и т.д.)
            $table->unsignedBigInteger('addressable_id');
            $table->string('addressable_type');
            
            // Географические связи
            $table->foreignId('country_id')->constrained('countries')->onDelete('cascade');
            $table->foreignId('region_id')->nullable()->constrained('regions')->onDelete('cascade');
            $table->foreignId('city_id')->nullable()->constrained('cities')->onDelete('cascade');
            
            // Адресные данные
            $table->string('street')->nullable();
            $table->string('house')->nullable();
            $table->string('building', 50)->nullable();
            $table->string('apartment', 50)->nullable();
            $table->string('floor', 10)->nullable();
            $table->string('entrance', 10)->nullable();
            $table->string('postal_code', 20)->nullable();
            
            // Координаты
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            
            // Дополнительная информация
            $table->text('additional_info')->nullable();
            $table->string('landmark')->nullable(); // Ориентир
            
            // Тип адреса
            $table->enum('address_type', [
                'home',        // Домашний
                'work',        // Рабочий
                'shipping',    // Для доставки
                'billing',     // Для оплаты
                'legal',       // Юридический
                'actual',      // Фактический
                'warehouse',   // Склад
                'shop',        // Магазин
                'office',      // Офис
                'other',       // Другой
            ])->default('home');
            
            // Флаги
            $table->boolean('is_primary')->default(false); // Основной адрес
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false); // Подтвержден ли адрес
            $table->boolean('is_public')->default(true); // Публичный ли адрес
            
            // Метаданные
            $table->string('name', 100)->nullable(); // Название адреса (например, "Мой дом", "Офис")
            $table->text('description')->nullable();
            
            // Временные метки
            $table->timestamps();
            $table->softDeletes();
            
            // Индексы
            $table->index(['addressable_id', 'addressable_type']);
            $table->index('addressable_type');
            $table->index('country_id');
            $table->index('region_id');
            $table->index('city_id');
            $table->index('postal_code');
            $table->index('address_type');
            $table->index('is_primary');
            $table->index('is_active');
            $table->index('is_verified');
            $table->index('is_public');
            $table->index('name');
            $table->index(['latitude', 'longitude']);
            
            // Составные индексы для частых запросов
            $table->index(['addressable_type', 'addressable_id', 'is_active']);
            $table->index(['country_id', 'region_id', 'city_id']);
            $table->index(['addressable_id', 'addressable_type', 'is_primary']);
        });
        
        // Добавляем ограничение для уникальности основных адресов
        Schema::table('addresses', function (Blueprint $table) {
            $table->unique(['addressable_id', 'addressable_type', 'is_primary'], 'unique_primary_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
