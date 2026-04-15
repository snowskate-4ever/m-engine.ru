<?php

declare(strict_types=1);

namespace App\Http\Controllers\api;

use App\Enums\ModerationStatus;
use App\Http\Controllers\Controller;
use App\Models\BlogComment;
use App\Models\BlogPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BlogCommentController extends Controller
{
    public function store(Request $request, BlogPost $blogPost): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        if (! $blogPost->comments_enabled) {
            return response()->json(['message' => 'Comments are disabled for this post.'], 422);
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:4000'],
            'parent_id' => ['nullable', 'integer', 'exists:blog_comments,id'],
        ]);

        $body = strip_tags($validated['body']);

        $comment = BlogComment::query()->create([
            'blog_post_id' => $blogPost->id,
            'author_user_id' => $user->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'body' => $body,
            'moderation_status' => ModerationStatus::Pending,
        ]);

        return response()->json(['id' => $comment->id], 201);
    }
}
