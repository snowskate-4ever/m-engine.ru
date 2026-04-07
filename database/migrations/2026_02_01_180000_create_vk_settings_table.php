<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vk_settings', function (Blueprint $table) {
            $table->id();
            $table->text('vk_access_token')->nullable();
            $table->text('vk_refresh_token')->nullable();
            $table->timestamp('vk_token_expires_at')->nullable();
            $table->string('vk_user_id', 64)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vk_settings');
    }
};
