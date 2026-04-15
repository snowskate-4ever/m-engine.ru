<?php

declare(strict_types=1);

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Support\Blog\BlogOwnerTypeResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PublicBlogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'owner_type' => ['required', 'string', 'max:32'],
            'owner_id' => ['required', 'integer', 'min:1'],
        ]);

        $class = BlogOwnerTypeResolver::classFromAlias($validated['owner_type']);
        $owner = $class::query()->findOrFail((int) $validated['owner_id']);

        $posts = BlogPost::query()
            ->where('owner_type', $owner->getMorphClass())
            ->where('owner_id', $owner->getKey())
            ->publishedApproved()
            ->orderByDesc('published_at')
            ->paginate(20);

        return response()->json($posts);
    }

    public function show(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'owner_type' => ['required', 'string', 'max:32'],
            'owner_id' => ['required', 'integer', 'min:1'],
            'slug' => ['required', 'string', 'max:128'],
        ]);

        $class = BlogOwnerTypeResolver::classFromAlias($validated['owner_type']);
        $owner = $class::query()->findOrFail((int) $validated['owner_id']);

        $post = BlogPost::query()
            ->where('owner_type', $owner->getMorphClass())
            ->where('owner_id', $owner->getKey())
            ->where('slug', $validated['slug'])
            ->publishedApproved()
            ->firstOrFail();

        $comments = collect();
        if ($post->comments_enabled) {
            $comments = $post->comments()
                ->where('moderation_status', \App\Enums\ModerationStatus::Approved)
                ->orderBy('id')
                ->get(['id', 'body', 'author_user_id', 'parent_id', 'created_at']);
        }

        return response()->json([
            'post' => $post->only(['id', 'title', 'slug', 'excerpt', 'body', 'comments_enabled', 'published_at']),
            'comments' => $comments,
        ]);
    }
}
