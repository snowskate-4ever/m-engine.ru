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
        // Для SQLite всегда пересоздаем таблицу с правильной структурой
        $this->recreateSocialsTable();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Возвращаем к базовой структуре
        $this->recreateBasicSocialsTable();
    }

    private function recreateSocialsTable(): void
    {
        // Сохраняем существующие данные
        $existingData = [];
        if (Schema::hasTable('socials')) {
            $existingData = DB::table('socials')->get()->toArray();
        }

        // Удаляем старую таблицу
        Schema::dropIfExists('socials');

        // Создаем новую таблицу с полной структурой
        Schema::create('socials', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();

            // Базовое поле для обратной совместимости
            $table->string('link')->nullable();

            // Полиморфная связь
            $table->unsignedBigInteger('socialable_id')->nullable();
            $table->string('socialable_type')->nullable();

            // Тип ссылки
            $table->enum('type', [
                'youtube',
                'instagram',
                'facebook',
                'vk',
                'telegram',
                'twitter',
                'tiktok',
                'portfolio',
                'website',
                'soundcloud',
                'spotify',
                'apple_music',
                'other'
            ])->nullable();

            // Название и описание
            $table->string('name')->nullable();
            $table->text('description')->nullable();

            // Порядок сортировки
            $table->integer('sort_order')->default(0);

            // Активность
            $table->boolean('active')->default(true);

            // Индексы
            $table->index(['socialable_id', 'socialable_type']);
            $table->index('type');
            $table->index('active');
            $table->index('sort_order');
        });

        // Восстанавливаем данные, если они были
        if (!empty($existingData)) {
            foreach ($existingData as $item) {
                $data = [
                    'id' => $item->id ?? null,
                    'link' => $item->link ?? null,
                    'socialable_id' => $item->socialable_id ?? null,
                    'socialable_type' => $item->socialable_type ?? null,
                    'type' => $item->type ?? null,
                    'name' => $item->name ?? null,
                    'description' => $item->description ?? null,
                    'sort_order' => $item->sort_order ?? 0,
                    'active' => $item->active ?? true,
                    'created_at' => $item->created_at ?? now(),
                    'updated_at' => $item->updated_at ?? now(),
                    'deleted_at' => $item->deleted_at ?? null,
                ];

                DB::table('socials')->insert($data);
            }
        }
    }

    private function recreateBasicSocialsTable(): void
    {
        // Возвращаем к базовой структуре без дополнительных полей
        Schema::dropIfExists('socials');

        Schema::create('socials', function (Blueprint $table) {
            $table->id();
            $table->string('link')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
