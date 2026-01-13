<?php

namespace Database\Seeders;

use App\Models\Genre;
use Illuminate\Database\Seeder;

class GenreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $genres = [
            // Популярная музыка
            ['name' => 'Поп', 'description' => 'Популярная музыка', 'sort_order' => 1],
            ['name' => 'Рок', 'description' => 'Рок-музыка', 'sort_order' => 2],
            ['name' => 'Рок-н-ролл', 'description' => 'Рок-н-ролл', 'sort_order' => 3],
            ['name' => 'Альтернативный рок', 'description' => 'Альтернативный рок', 'sort_order' => 4],
            ['name' => 'Инди-рок', 'description' => 'Инди-рок', 'sort_order' => 5],
            ['name' => 'Панк-рок', 'description' => 'Панк-рок', 'sort_order' => 6],
            ['name' => 'Метал', 'description' => 'Метал', 'sort_order' => 7],
            ['name' => 'Хард-рок', 'description' => 'Хард-рок', 'sort_order' => 8],
            
            // Электронная музыка
            ['name' => 'Электронная музыка', 'description' => 'Электронная музыка', 'sort_order' => 20],
            ['name' => 'Хаус', 'description' => 'Хаус', 'sort_order' => 21],
            ['name' => 'Техно', 'description' => 'Техно', 'sort_order' => 22],
            ['name' => 'Транс', 'description' => 'Транс', 'sort_order' => 23],
            ['name' => 'Драм-н-бейс', 'description' => 'Драм-н-бейс', 'sort_order' => 24],
            ['name' => 'Дабстеп', 'description' => 'Дабстеп', 'sort_order' => 25],
            ['name' => 'EDM', 'description' => 'Electronic Dance Music', 'sort_order' => 26],
            ['name' => 'Эмбиент', 'description' => 'Эмбиент', 'sort_order' => 27],
            
            // Джаз и блюз
            ['name' => 'Джаз', 'description' => 'Джаз', 'sort_order' => 30],
            ['name' => 'Свинг', 'description' => 'Свинг', 'sort_order' => 31],
            ['name' => 'Биг-бэнд', 'description' => 'Биг-бэнд', 'sort_order' => 32],
            ['name' => 'Блюз', 'description' => 'Блюз', 'sort_order' => 33],
            ['name' => 'Соул', 'description' => 'Соул', 'sort_order' => 34],
            ['name' => 'Фанк', 'description' => 'Фанк', 'sort_order' => 35],
            ['name' => 'R&B', 'description' => 'Rhythm and Blues', 'sort_order' => 36],
            
            // Классическая музыка
            ['name' => 'Классическая музыка', 'description' => 'Классическая музыка', 'sort_order' => 40],
            ['name' => 'Барокко', 'description' => 'Барокко', 'sort_order' => 41],
            ['name' => 'Романтизм', 'description' => 'Романтизм', 'sort_order' => 42],
            ['name' => 'Современная классика', 'description' => 'Современная классика', 'sort_order' => 43],
            ['name' => 'Камерная музыка', 'description' => 'Камерная музыка', 'sort_order' => 44],
            ['name' => 'Оперная музыка', 'description' => 'Оперная музыка', 'sort_order' => 45],
            
            // Народная и этническая музыка
            ['name' => 'Народная музыка', 'description' => 'Народная музыка', 'sort_order' => 50],
            ['name' => 'Русская народная', 'description' => 'Русская народная музыка', 'sort_order' => 51],
            ['name' => 'Этническая музыка', 'description' => 'Этническая музыка', 'sort_order' => 52],
            ['name' => 'Фолк', 'description' => 'Фолк', 'sort_order' => 53],
            ['name' => 'Кельтская музыка', 'description' => 'Кельтская музыка', 'sort_order' => 54],
            
            // Хип-хоп и рэп
            ['name' => 'Хип-хоп', 'description' => 'Хип-хоп', 'sort_order' => 60],
            ['name' => 'Рэп', 'description' => 'Рэп', 'sort_order' => 61],
            ['name' => 'Трэп', 'description' => 'Трэп', 'sort_order' => 62],
            
            // Латиноамериканская музыка
            ['name' => 'Латино', 'description' => 'Латиноамериканская музыка', 'sort_order' => 70],
            ['name' => 'Сальса', 'description' => 'Сальса', 'sort_order' => 71],
            ['name' => 'Бачата', 'description' => 'Бачата', 'sort_order' => 72],
            ['name' => 'Реггетон', 'description' => 'Реггетон', 'sort_order' => 73],
            ['name' => 'Босса-нова', 'description' => 'Босса-нова', 'sort_order' => 74],
            ['name' => 'Самба', 'description' => 'Самба', 'sort_order' => 75],
            
            // Кантри и вестерн
            ['name' => 'Кантри', 'description' => 'Кантри', 'sort_order' => 80],
            ['name' => 'Кантри-рок', 'description' => 'Кантри-рок', 'sort_order' => 81],
            ['name' => 'Блюграсс', 'description' => 'Блюграсс', 'sort_order' => 82],
            
            // Регги
            ['name' => 'Регги', 'description' => 'Регги', 'sort_order' => 90],
            ['name' => 'Ска', 'description' => 'Ска', 'sort_order' => 91],
            ['name' => 'Дэнсхолл', 'description' => 'Дэнсхолл', 'sort_order' => 92],
            
            // Другие жанры
            ['name' => 'Госпел', 'description' => 'Госпел', 'sort_order' => 100],
            ['name' => 'Христианская музыка', 'description' => 'Христианская музыка', 'sort_order' => 101],
            ['name' => 'Нью-эйдж', 'description' => 'Нью-эйдж', 'sort_order' => 102],
            ['name' => 'Саундтреки', 'description' => 'Музыка для кино и телевидения', 'sort_order' => 103],
            ['name' => 'Детская музыка', 'description' => 'Детская музыка', 'sort_order' => 104],
        ];

        foreach ($genres as $genre) {
            Genre::updateOrCreate(
                ['name' => $genre['name']],
                [
                    'description' => $genre['description'],
                    'sort_order' => $genre['sort_order'],
                    'active' => true,
                ]
            );
        }
    }
}
