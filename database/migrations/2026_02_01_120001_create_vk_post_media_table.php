<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vk_post_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vk_post_id')->constrained('vk_posts')->cascadeOnDelete();
            $table->string('type', 20); // photo, audio
            $table->string('vk_url', 1024)->nullable(); // исходный URL в VK
            $table->string('path', 1024)->nullable(); // путь в нашем хранилище (storage/app/vk-posts/...)
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vk_post_media');
    }
};
