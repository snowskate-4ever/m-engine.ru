<?php

namespace Database\Seeders;

use MoonShine\Laravel\Models\MoonshineUserRole;
use Illuminate\Database\Seeder;

class MoonShineUserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'id' => MoonshineUserRole::DEFAULT_ROLE_ID,
                'name' => 'Admin',
            ],
            [
                'name' => 'Manager',
            ],
            [
                'name' => 'Editor',
            ],
        ];

        foreach ($roles as $role) {
            if (isset($role['id'])) {
                // Для роли с фиксированным ID используем updateOrCreate
                MoonshineUserRole::updateOrCreate(
                    ['id' => $role['id']],
                    ['name' => $role['name']]
                );
            } else {
                // Для остальных ролей используем updateOrCreate по имени
                MoonshineUserRole::updateOrCreate(
                    ['name' => $role['name']],
                    ['name' => $role['name']]
                );
            }
        }

        $this->command->info('Роли MoonShine успешно добавлены!');
    }
}
