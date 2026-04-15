<?php

declare(strict_types=1);

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\BlogSubscription;
use App\Support\Blog\BlogOwnerTypeResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BlogSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $validated = $request->validate([
            'owner_type' => ['required', 'string', 'max:32'],
            'owner_id' => ['required', 'integer', 'min:1'],
        ]);

        $class = BlogOwnerTypeResolver::classFromAlias($validated['owner_type']);
        $owner = $class::query()->findOrFail((int) $validated['owner_id']);

        $sub = BlogSubscription::query()->firstOrCreate([
            'subscriber_user_id' => $user->id,
            'owner_type' => $owner->getMorphClass(),
            'owner_id' => $owner->getKey(),
        ]);

        return response()->json(['id' => $sub->id, 'subscribed' => true], $sub->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $validated = $request->validate([
            'owner_type' => ['required', 'string', 'max:32'],
            'owner_id' => ['required', 'integer', 'min:1'],
        ]);

        $class = BlogOwnerTypeResolver::classFromAlias($validated['owner_type']);
        $owner = $class::query()->findOrFail((int) $validated['owner_id']);

        BlogSubscription::query()
            ->where('subscriber_user_id', $user->id)
            ->where('owner_type', $owner->getMorphClass())
            ->where('owner_id', $owner->getKey())
            ->delete();

        return response()->json(['subscribed' => false]);
    }
}
