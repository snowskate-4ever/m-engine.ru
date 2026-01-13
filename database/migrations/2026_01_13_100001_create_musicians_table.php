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
        Schema::create('musicians', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description');
            
            // Связь с пользователем системы
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Статус и активность
            $table->boolean('active')->default(true);
            
            // Фото/аватар
            $table->string('photo')->nullable();
            $table->string('avatar')->nullable();
            
            // Профессиональные характеристики
            // instruments и genres теперь через связи many-to-many
            // experience_years удалено - опыт считается от старта работы ресурса
            $table->text('bio')->nullable(); // Краткая биография
            
            // Рейтинг и цены
            $table->decimal('rating', 3, 2)->nullable()->default(0); // Рейтинг от 0 до 5
            $table->decimal('price_per_hour', 10, 2)->nullable(); // Цена за час работы
            
            // Личная информация
            $table->date('birth_date')->nullable(); // Дата рождения
            $table->enum('gender', ['male', 'female', 'other'])->nullable(); // Пол
            
            // Образование
            $table->text('education')->nullable(); // Образование
            
            // Портфолио и медиа
            // portfolio_url, video_url, media_urls удалены - хранятся в таблице socials
            
            // Доступность и расписание
            $table->json('availability')->nullable(); // Расписание доступности (JSON)
            $table->boolean('available_for_booking')->default(true); // Доступен для бронирования
            $table->boolean('is_session')->default(false); // Работает как сессионный музыкант
            
            // Дополнительная информация
            $table->text('notes')->nullable(); // Заметки/примечания
            $table->json('metadata')->nullable(); // Дополнительные метаданные (JSON)
            
            // Мягкое удаление
            $table->softDeletes();
            
            $table->timestamps();
            
            // Индексы
            $table->index('user_id');
            $table->index('active');
            $table->index('available_for_booking');
            $table->index('rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('musicians');
    }
};
