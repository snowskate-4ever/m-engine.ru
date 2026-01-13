<?php

namespace Database\Seeders;

use MoonShine\Laravel\Models\MoonshineUser;
use MoonShine\Laravel\Models\MoonshineUserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MoonShineUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Получаем роль Admin
        $adminRole = MoonshineUserRole::where('name', 'Admin')->first();
        
        if (!$adminRole) {
            $this->command->error('Роль Admin не найдена. Сначала запустите MoonShineUserRoleSeeder.');
            return;
        }

        $users = [
            [
                'name' => 'madmd',
                'email' => 'mad.md@yandex.ru',
                'password' => 'password', // Будет захеширован
                'moonshine_user_role_id' => $adminRole->id,
            ],
        ];

        foreach ($users as $userData) {
            MoonshineUser::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make($userData['password']),
                    'moonshine_user_role_id' => $userData['moonshine_user_role_id'],
                ]
            );
        }

        $this->command->info('Пользователи MoonShine успешно добавлены!');
    }
}
