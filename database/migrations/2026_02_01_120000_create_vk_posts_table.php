<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vk_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vk_tracking_id')->constrained('vk_trackings')->cascadeOnDelete();
            $table->unsignedBigInteger('vk_post_id'); // id поста в VK (wall)
            $table->bigInteger('from_id')->nullable(); // id автора в VK
            $table->bigInteger('signer_id')->nullable(); // id подписанта (если от имени группы)
            $table->text('text')->nullable();
            $table->json('raw_json')->nullable(); // полный сырой пост; после обработки можно очистить
            $table->timestamp('posted_at')->nullable(); // дата публикации в VK
            $table->timestamp('processed_at')->nullable(); // когда обработан (для удаления raw_json)
            $table->timestamps();

            $table->unique(['vk_tracking_id', 'vk_post_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vk_posts');
    }
};
