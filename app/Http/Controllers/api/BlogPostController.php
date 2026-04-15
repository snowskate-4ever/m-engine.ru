<?php

declare(strict_types=1);

namespace App\Http\Controllers\api;

use App\Enums\ModerationStatus;
use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Support\Blog\BlogBodySanitizer;
use App\Support\Blog\BlogOwnerTypeResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class BlogPostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $posts = BlogPost::query()
            ->where('author_user_id', $user->id)
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json($posts);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $validated = $request->validate([
            'owner_type' => ['required', 'string', 'max:32'],
            'owner_id' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string'],
            'comments_enabled' => ['sometimes', 'boolean'],
            'publish_now' => ['sometimes', 'boolean'],
        ]);

        $class = BlogOwnerTypeResolver::classFromAlias($validated['owner_type']);
        $owner = $class::query()->findOrFail((int) $validated['owner_id']);

        $slug = $this->uniqueSlugForOwner($owner, Str::slug($validated['title']));
        $body = BlogBodySanitizer::sanitize($validated['body']);

        $post = BlogPost::query()->create([
            'author_user_id' => $user->id,
            'owner_type' => $owner->getMorphClass(),
            'owner_id' => $owner->getKey(),
            'title' => $validated['title'],
            'slug' => $slug,
            'excerpt' => $validated['excerpt'] ?? null,
            'body' => $body,
            'comments_enabled' => (bool) ($validated['comments_enabled'] ?? false),
            'moderation_status' => ModerationStatus::Pending,
            'published_at' => ! empty($validated['publish_now']) ? now() : null,
        ]);

        return response()->json(['id' => $post->id, 'slug' => $post->slug], 201);
    }

    public function update(Request $request, BlogPost $blogPost): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && (int) $blogPost->author_user_id === (int) $user->id, 403);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['sometimes', 'string'],
            'comments_enabled' => ['sometimes', 'boolean'],
        ]);

        if (isset($validated['body'])) {
            $validated['body'] = BlogBodySanitizer::sanitize($validated['body']);
        }

        if (isset($validated['title'])) {
            $validated['slug'] = $this->uniqueSlugForOwner(
                $blogPost->owner,
                Str::slug($validated['title']),
                $blogPost->id,
            );
        }

        $blogPost->fill($validated);
        if ($blogPost->isDirty(['title', 'body', 'excerpt'])) {
            $blogPost->moderation_status = ModerationStatus::Pending;
        }
        $blogPost->save();

        return response()->json(['id' => $blogPost->id, 'slug' => $blogPost->slug]);
    }

    private function uniqueSlugForOwner($owner, string $baseSlug, ?int $ignorePostId = null): string
    {
        $slug = $baseSlug !== '' ? $baseSlug : 'post';
        $i = 0;
        do {
            $candidate = $slug.($i > 0 ? '-'.$i : '');
            $q = BlogPost::query()
                ->where('owner_type', $owner->getMorphClass())
                ->where('owner_id', $owner->getKey())
                ->where('slug', $candidate);
            if ($ignorePostId !== null) {
                $q->where('id', '!=', $ignorePostId);
            }
            $exists = $q->exists();
            $i++;
        } while ($exists);

        return $candidate;
    }
}
