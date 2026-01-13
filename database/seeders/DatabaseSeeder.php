<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Пользователи
        $this->call([
            UserSeeder::class,
        ]);

        // MoonShine роли и пользователи
        $this->call([
            MoonShineUserRoleSeeder::class,
            MoonShineUserSeeder::class,
        ]);

        // Заполнение таблиц инструментов и жанров
        $this->call([
            InstrumentSeeder::class,
            GenreSeeder::class,
        ]);

        // Заполнение регионов и городов России
        $this->call([
            RussiaRegionsSeeder::class,
            RussiaCitiesSeeder::class,
        ]);
    }
}
