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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->after('email')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Для SQLite используем более надежный подход
        $driver = \DB::getDriverName();

        if ($driver === 'sqlite') {
            // Удаляем индекс, если он существует
            \DB::statement('DROP INDEX IF EXISTS users_phone_index');

            // Удаляем колонку через пересоздание таблицы
            $this->recreateUsersTableWithoutPhone();
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('phone');
            });
        }
    }

    private function recreateUsersTableWithoutPhone(): void
    {
        $users = \DB::table('users')->get();

        \DB::statement('DROP TABLE IF EXISTS users');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        foreach ($users as $user) {
            \DB::table('users')->insert([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'password' => $user->password,
                'two_factor_secret' => $user->two_factor_secret ?? null,
                'two_factor_recovery_codes' => $user->two_factor_recovery_codes ?? null,
                'two_factor_confirmed_at' => $user->two_factor_confirmed_at ?? null,
                'remember_token' => $user->remember_token,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]);
        }
    }
};
