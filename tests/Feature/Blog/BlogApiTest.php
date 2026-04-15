<?php

declare(strict_types=1);

namespace Tests\Feature\Blog;

use App\Enums\ModerationStatus;
use App\Models\BlogPost;
use App\Models\Musician;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class BlogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_blog_lists_approved_posts(): void
    {
        $user = User::factory()->create();
        $m = Musician::query()->create([
            'name' => 'BlogOwner '.uniqid(),
            'description' => 'd',
            'user_id' => $user->id,
        ]);

        BlogPost::query()->create([
            'author_user_id' => $user->id,
            'owner_type' => $m->getMorphClass(),
            'owner_id' => $m->id,
            'title' => 'Hello',
            'slug' => 'hello',
            'body' => '<p>Hi</p>',
            'comments_enabled' => false,
            'moderation_status' => ModerationStatus::Approved,
            'published_at' => now()->subMinute(),
        ]);

        $this->assertSame(1, BlogPost::query()->count());
        $this->assertSame(1, BlogPost::query()->publishedApproved()->count());
        $this->assertDatabaseHas('blog_posts', [
            'owner_id' => $m->id,
            'owner_type' => Musician::class,
        ]);

        $r = $this->getJson('/api/public/blog-posts?owner_type=musician&owner_id='.$m->id);
        $r->assertOk();
        $this->assertCount(1, (array) $r->json('data'));
    }

    public function test_comment_rejected_when_disabled(): void
    {
        $user = User::factory()->create();
        $m = Musician::query()->create([
            'name' => 'M '.uniqid(),
            'description' => 'd',
            'user_id' => $user->id,
        ]);
        $post = BlogPost::query()->create([
            'author_user_id' => $user->id,
            'owner_type' => $m->getMorphClass(),
            'owner_id' => $m->id,
            'title' => 'T',
            'slug' => 't',
            'body' => 'b',
            'comments_enabled' => false,
            'moderation_status' => ModerationStatus::Approved,
            'published_at' => now(),
        ]);

        Sanctum::actingAs($user);
        $this->postJson("/api/blog-posts/{$post->id}/comments", ['body' => 'Nice'])
            ->assertStatus(422);
    }
}
