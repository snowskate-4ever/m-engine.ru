<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'madmd',
                'email' => 'mad.md@yandex.ru',
                'phone' => '+79531704190',
                'password' => 'password',
            ],
            [
                'name' => 'Василий Васильев',
                'email' => 'snowskate-4ever@mail.ru',
                'phone' => null,
                'password' => 'password',
            ],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'password' => $data['password'], // захешируется через каст в модели
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
