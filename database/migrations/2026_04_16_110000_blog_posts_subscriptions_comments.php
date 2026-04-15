<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_posts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('author_user_id')->constrained('users')->cascadeOnDelete();
            $table->morphs('owner');
            $table->string('title');
            $table->string('slug', 128);
            $table->text('excerpt')->nullable();
            $table->longText('body');
            $table->boolean('comments_enabled')->default(false);
            $table->string('moderation_status', 32)->default('pending')->index();
            $table->timestampTz('published_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['owner_type', 'owner_id', 'slug']);
        });

        Schema::create('blog_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('subscriber_user_id')->constrained('users')->cascadeOnDelete();
            $table->morphs('owner');
            $table->timestamps();

            $table->unique(['subscriber_user_id', 'owner_type', 'owner_id'], 'blog_sub_unique');
        });

        Schema::create('blog_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('blog_post_id')->constrained('blog_posts')->cascadeOnDelete();
            $table->foreignId('author_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('blog_comments')->nullOnDelete();
            $table->text('body');
            $table->string('moderation_status', 32)->default('pending')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_comments');
        Schema::dropIfExists('blog_subscriptions');
        Schema::dropIfExists('blog_posts');
    }
};
