<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('vk_access_token')->nullable()->after('telegram_id');
            $table->text('vk_refresh_token')->nullable()->after('vk_access_token');
            $table->timestamp('vk_token_expires_at')->nullable()->after('vk_refresh_token');
            $table->unsignedBigInteger('vk_user_id')->nullable()->after('vk_token_expires_at');
            $table->index('vk_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $this->recreateUsersTableWithoutVkTokens();
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['vk_user_id']);
                $table->dropColumn([
                    'vk_access_token',
                    'vk_refresh_token',
                    'vk_token_expires_at',
                    'vk_user_id',
                ]);
            });
        }
    }

    private function recreateUsersTableWithoutVkTokens(): void
    {
        $users = DB::table('users')->get();

        DB::statement('DROP TABLE IF EXISTS users');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->string('registration_channel')->nullable();
            $table->json('registration_metadata')->nullable();
            $table->unsignedBigInteger('telegram_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->index('registration_channel');
            $table->index('telegram_id');
        });

        foreach ($users as $user) {
            DB::table('users')->insert([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? null,
                'email_verified_at' => $user->email_verified_at,
                'password' => $user->password,
                'two_factor_secret' => $user->two_factor_secret ?? null,
                'two_factor_recovery_codes' => $user->two_factor_recovery_codes ?? null,
                'two_factor_confirmed_at' => $user->two_factor_confirmed_at ?? null,
                'registration_channel' => $user->registration_channel ?? null,
                'registration_metadata' => $user->registration_metadata ?? null,
                'telegram_id' => $user->telegram_id ?? null,
                'remember_token' => $user->remember_token,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]);
        }
    }
};
