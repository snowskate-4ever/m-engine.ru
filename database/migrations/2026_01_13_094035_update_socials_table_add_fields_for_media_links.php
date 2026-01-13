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
        Schema::table('socials', function (Blueprint $table) {
            // Добавляем полиморфную связь для связи с resources и musicians
            $table->unsignedBigInteger('socialable_id')->nullable()->after('id');
            $table->string('socialable_type')->nullable()->after('socialable_id');
            
            // Тип ссылки (YouTube, Instagram, Facebook, Portfolio и т.д.)
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
            ])->nullable()->after('link');
            
            // Название/описание ссылки
            $table->string('name')->nullable()->after('type');
            $table->text('description')->nullable()->after('name');
            
            // Порядок сортировки
            $table->integer('sort_order')->default(0)->after('description');
            
            // Активность
            $table->boolean('active')->default(true)->after('sort_order');
            
            // Индексы
            $table->index(['socialable_id', 'socialable_type']);
            $table->index('type');
            $table->index('active');
            $table->index('sort_order');
        });
        
        // Переносим данные из resource_id в полиморфную связь
        DB::table('socials')->whereNotNull('resource_id')->update([
            'socialable_id' => DB::raw('resource_id'),
            'socialable_type' => 'App\\Models\\Resource',
        ]);
        
        // Удаляем старое поле resource_id после переноса данных
        Schema::table('socials', function (Blueprint $table) {
            // Пытаемся удалить внешний ключ, если он существует
            // Используем стандартное имя внешнего ключа Laravel
            try {
                $table->dropForeign(['resource_id']);
            } catch (\Exception $e) {
                // Внешний ключ может не существовать, это нормально
            }
            
            $table->dropColumn('resource_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('socials', function (Blueprint $table) {
            // Восстанавливаем resource_id
            $table->foreignId('resource_id')->nullable()->after('id');
        });
        
        // Переносим данные обратно
        DB::table('socials')
            ->where('socialable_type', 'App\\Models\\Resource')
            ->update([
                'resource_id' => DB::raw('socialable_id'),
            ]);
        
        Schema::table('socials', function (Blueprint $table) {
            // Удаляем добавленные поля
            $table->dropIndex(['socialable_id', 'socialable_type']);
            $table->dropIndex(['type']);
            $table->dropIndex(['active']);
            $table->dropIndex(['sort_order']);
            
            $table->dropColumn([
                'socialable_id',
                'socialable_type',
                'type',
                'name',
                'description',
                'sort_order',
                'active',
            ]);
        });
    }
};
