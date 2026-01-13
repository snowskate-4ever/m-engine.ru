<?php

namespace Database\Seeders;

use App\Models\Instrument;
use Illuminate\Database\Seeder;

class InstrumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $instruments = [
            // Струнные инструменты
            ['name' => 'Гитара', 'description' => 'Акустическая и электрическая гитара', 'sort_order' => 1],
            ['name' => 'Бас-гитара', 'description' => 'Электрическая бас-гитара', 'sort_order' => 2],
            ['name' => 'Скрипка', 'description' => 'Классическая скрипка', 'sort_order' => 3],
            ['name' => 'Виолончель', 'description' => 'Виолончель', 'sort_order' => 4],
            ['name' => 'Альт', 'description' => 'Альт', 'sort_order' => 5],
            ['name' => 'Контрабас', 'description' => 'Контрабас', 'sort_order' => 6],
            ['name' => 'Арфа', 'description' => 'Арфа', 'sort_order' => 7],
            ['name' => 'Балалайка', 'description' => 'Русская балалайка', 'sort_order' => 8],
            ['name' => 'Домра', 'description' => 'Русская домра', 'sort_order' => 9],
            ['name' => 'Мандолина', 'description' => 'Мандолина', 'sort_order' => 10],
            ['name' => 'Банджо', 'description' => 'Банджо', 'sort_order' => 11],
            ['name' => 'Укулеле', 'description' => 'Укулеле', 'sort_order' => 12],
            
            // Клавишные инструменты
            ['name' => 'Фортепиано', 'description' => 'Акустическое фортепиано', 'sort_order' => 20],
            ['name' => 'Пианино', 'description' => 'Пианино', 'sort_order' => 21],
            ['name' => 'Синтезатор', 'description' => 'Электронный синтезатор', 'sort_order' => 22],
            ['name' => 'Цифровое пианино', 'description' => 'Цифровое пианино', 'sort_order' => 23],
            ['name' => 'Орган', 'description' => 'Орган', 'sort_order' => 24],
            ['name' => 'Аккордеон', 'description' => 'Аккордеон', 'sort_order' => 25],
            ['name' => 'Баян', 'description' => 'Русский баян', 'sort_order' => 26],
            ['name' => 'Гармонь', 'description' => 'Гармонь', 'sort_order' => 27],
            ['name' => 'Клавесин', 'description' => 'Клавесин', 'sort_order' => 28],
            
            // Духовые инструменты
            ['name' => 'Саксофон', 'description' => 'Саксофон (альт, тенор, сопрано, баритон)', 'sort_order' => 30],
            ['name' => 'Труба', 'description' => 'Труба', 'sort_order' => 31],
            ['name' => 'Тромбон', 'description' => 'Тромбон', 'sort_order' => 32],
            ['name' => 'Туба', 'description' => 'Туба', 'sort_order' => 33],
            ['name' => 'Валторна', 'description' => 'Валторна', 'sort_order' => 34],
            ['name' => 'Флейта', 'description' => 'Поперечная флейта', 'sort_order' => 35],
            ['name' => 'Блокфлейта', 'description' => 'Блокфлейта', 'sort_order' => 36],
            ['name' => 'Кларнет', 'description' => 'Кларнет', 'sort_order' => 37],
            ['name' => 'Гобой', 'description' => 'Гобой', 'sort_order' => 38],
            ['name' => 'Фагот', 'description' => 'Фагот', 'sort_order' => 39],
            
            // Ударные инструменты
            ['name' => 'Ударная установка', 'description' => 'Барабанная установка', 'sort_order' => 50],
            ['name' => 'Перкуссия', 'description' => 'Различные перкуссионные инструменты', 'sort_order' => 51],
            ['name' => 'Конго', 'description' => 'Конго', 'sort_order' => 52],
            ['name' => 'Бонго', 'description' => 'Бонго', 'sort_order' => 53],
            ['name' => 'Джембе', 'description' => 'Джембе', 'sort_order' => 54],
            ['name' => 'Ксилофон', 'description' => 'Ксилофон', 'sort_order' => 55],
            ['name' => 'Маракасы', 'description' => 'Маракасы', 'sort_order' => 56],
            ['name' => 'Тамбурин', 'description' => 'Тамбурин', 'sort_order' => 57],
            ['name' => 'Кахон', 'description' => 'Кахон', 'sort_order' => 58],
            
            // Электронные инструменты
            ['name' => 'DJ-оборудование', 'description' => 'DJ-пульт, вертушки, контроллеры', 'sort_order' => 60],
            ['name' => 'Драм-машина', 'description' => 'Электронная драм-машина', 'sort_order' => 61],
            ['name' => 'Семплер', 'description' => 'Семплер', 'sort_order' => 62],
            
            // Вокал
            ['name' => 'Вокал', 'description' => 'Вокал (различные стили)', 'sort_order' => 70],
            ['name' => 'Бэк-вокал', 'description' => 'Бэк-вокал', 'sort_order' => 71],
            
            // Другие
            ['name' => 'Гармоника', 'description' => 'Губная гармоника', 'sort_order' => 80],
            ['name' => 'Мелодика', 'description' => 'Мелодика', 'sort_order' => 81],
        ];

        foreach ($instruments as $instrument) {
            Instrument::updateOrCreate(
                ['name' => $instrument['name']],
                [
                    'description' => $instrument['description'],
                    'sort_order' => $instrument['sort_order'],
                    'active' => true,
                ]
            );
        }
    }
}
