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
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 2)->unique(); // ISO 3166-1 alpha-2
            $table->string('phone_code', 10)->nullable();
            $table->string('currency_code', 3)->nullable(); // USD, EUR, RUB
            $table->string('currency_symbol', 10)->nullable(); // $, €, ₽
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes(); // для мягкого удаления
            
            // Индексы
            $table->index('name');
            $table->index('code');
            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
