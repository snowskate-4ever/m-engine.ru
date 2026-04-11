<?php

declare(strict_types=1);

namespace App\Http\Controllers\api;

use App\Enums\MusicMembershipRole;
use App\Enums\MusicMembershipStatus;
use App\Http\Controllers\Controller;
use App\Models\ConcertVenue;
use App\Models\MusicProfileMembership;
use App\Models\Musician;
use App\Models\Peformer;
use App\Models\User;
use App\Services\Music\MusicProfileMembershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MusicProfileMembershipController extends Controller
{
    public function __construct(
        private readonly MusicProfileMembershipService $membershipService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $items = $request->user()->musicProfileMemberships()->latest()->get();

        return response()->json([
            'data' => $items->map(fn (MusicProfileMembership $item) => $this->toArray($item)),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entity_type' => ['required', 'string', 'in:concert_venue,performer,musician'],
            'entity_id' => ['required', 'integer', 'min:1'],
            'member_user_id' => ['required', 'integer', 'exists:users,id'],
            'role' => ['required', 'string', 'in:venue_representative,manager'],
        ]);

        $entity = $this->resolveEntity($validated['entity_type'], (int) $validated['entity_id']);
        $membership = $this->membershipService->invite(
            $request->user(),
            $entity,
            User::query()->findOrFail((int) $validated['member_user_id']),
            MusicMembershipRole::from($validated['role']),
        );

        return response()->json(['data' => $this->toArray($membership)], 201);
    }

    public function respond(Request $request, MusicProfileMembership $membership): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:accepted,declined'],
        ]);

        $membership = $this->membershipService->respond(
            $request->user(),
            $membership,
            MusicMembershipStatus::from($validated['status']),
        );

        return response()->json(['data' => $this->toArray($membership)]);
    }

    public function revoke(Request $request, MusicProfileMembership $membership): JsonResponse
    {
        $this->membershipService->revoke($request->user(), $membership);

        return response()->json(['ok' => true]);
    }

    private function resolveEntity(string $type, int $id): ConcertVenue|Peformer|Musician
    {
        return match ($type) {
            'concert_venue' => ConcertVenue::query()->findOrFail($id),
            'performer' => Peformer::query()->findOrFail($id),
            'musician' => Musician::query()->findOrFail($id),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(MusicProfileMembership $membership): array
    {
        return [
            'id' => $membership->id,
            'member_user_id' => $membership->member_user_id,
            'entity_type' => $membership->entity_type,
            'entity_id' => $membership->entity_id,
            'role' => $membership->role?->value ?? (string) $membership->role,
            'status' => $membership->status?->value ?? (string) $membership->status,
            'invited_by_user_id' => $membership->invited_by_user_id,
            'responded_at' => $membership->responded_at?->toIso8601String(),
            'created_at' => $membership->created_at?->toIso8601String(),
        ];
    }
}
