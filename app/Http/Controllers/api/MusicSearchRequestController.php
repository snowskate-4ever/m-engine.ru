<?php

declare(strict_types=1);

namespace App\Http\Controllers\api;

use App\Enums\SearchGoal;
use App\Http\Controllers\Controller;
use App\Models\ConcertVenue;
use App\Models\Musician;
use App\Models\Peformer;
use App\Models\ProducerCenter;
use App\Models\RecordLabel;
use App\Models\Rehersal;
use App\Models\School;
use App\Models\SearchRequest;
use App\Models\SearchRequestResponse;
use App\Models\Shop;
use App\Models\Studio;
use App\Models\User;
use App\Services\Music\SearchGoalEligibilityService;
use App\Services\Music\SearchRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MusicSearchRequestController extends Controller
{
    public function __construct(
        private readonly SearchRequestService $searchRequestService,
        private readonly SearchGoalEligibilityService $searchGoalEligibilityService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $items = SearchRequest::query()
            ->with('initiator')
            ->where('created_by_user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return response()->json([
            'data' => $items->map(fn (SearchRequest $item) => $this->toArray($item))->values(),
        ]);
    }

    public function feed(Request $request): JsonResponse
    {
        $items = SearchRequest::query()
            ->where('ad_status', 'active')
            ->where('moderation_status', 'approved')
            ->with('initiator')
            ->orderByDesc('published_at')
            ->limit(100)
            ->get();

        return response()->json([
            'data' => $items->map(fn (SearchRequest $item) => $this->toArray($item))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $resolvedInitiatorType = $this->resolveInitiatorType($request);
        $allowedGoals = $this->searchGoalEligibilityService
            ->allowedGoalValuesForInitiator($resolvedInitiatorType);

        $validated = $request->validate([
            'search_goal' => ['required', Rule::in($allowedGoals)],
            'initiator_type' => ['nullable', 'string', Rule::in($this->searchGoalEligibilityService->supportedInitiatorTypes())],
            'initiator_id' => ['nullable', 'integer', 'min:1'],
            'criteria' => ['nullable', 'array'],
        ]);

        try {
            $created = $this->searchRequestService->createUsingActorContext(
                $request->user(),
                SearchGoal::from((string) $validated['search_goal']),
                is_array($validated['criteria'] ?? null) ? $validated['criteria'] : [],
                isset($validated['initiator_type']) ? (string) $validated['initiator_type'] : null,
                isset($validated['initiator_id']) ? (int) $validated['initiator_id'] : null,
            );
        } catch (\InvalidArgumentException) {
            return response()->json([
                'ok' => false,
                'message' => __('ui.music.search_requests_goal_not_allowed'),
            ], 422);
        }

        return response()->json([
            'data' => $this->toArray($created),
        ], 201);
    }

    public function cancel(Request $request, SearchRequest $searchRequest): JsonResponse
    {
        $owned = $this->ownedRequestOrFail($request, $searchRequest);

        try {
            $this->searchRequestService->cancel($owned);
        } catch (\InvalidArgumentException $exception) {
            return response()->json([
                'ok' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'data' => $this->toArray($owned->fresh()),
        ]);
    }

    public function reopen(Request $request, SearchRequest $searchRequest): JsonResponse
    {
        $owned = $this->ownedRequestOrFail($request, $searchRequest);

        try {
            $this->searchRequestService->reopen($owned);
        } catch (\InvalidArgumentException $exception) {
            return response()->json([
                'ok' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'data' => $this->toArray($owned->fresh()),
        ]);
    }

    public function respond(Request $request, SearchRequest $searchRequest): JsonResponse
    {
        if (($searchRequest->ad_status?->value ?? (string) $searchRequest->ad_status) !== 'active') {
            return response()->json([
                'ok' => false,
                'message' => 'Ad is not active.',
            ], 422);
        }

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $response = SearchRequestResponse::query()->updateOrCreate(
            [
                'search_request_id' => $searchRequest->id,
                'responder_user_id' => $request->user()->id,
            ],
            [
                'message' => isset($validated['message']) ? (string) $validated['message'] : null,
                'status' => 'pending',
                'contact_unlocked_at' => now(),
            ]
        );

        return response()->json([
            'ok' => true,
            'data' => [
                'id' => $response->id,
                'search_request_id' => $response->search_request_id,
                'responder_user_id' => $response->responder_user_id,
                'status' => $response->status,
                'contact_unlocked_at' => $response->contact_unlocked_at?->toIso8601String(),
            ],
        ], 201);
    }

    public function responses(Request $request, SearchRequest $searchRequest): JsonResponse
    {
        $owned = $this->ownedRequestOrFail($request, $searchRequest);
        $items = $owned->responses()->with('responder:id,name,email,phone')->latest()->get();

        return response()->json([
            'data' => $items->map(static function (SearchRequestResponse $item): array {
                return [
                    'id' => $item->id,
                    'responder_user_id' => $item->responder_user_id,
                    'responder_name' => $item->responder?->name,
                    'responder_email' => $item->responder?->email,
                    'responder_phone' => $item->responder?->phone,
                    'message' => $item->message,
                    'status' => $item->status,
                    'created_at' => $item->created_at?->toIso8601String(),
                ];
            })->values(),
        ]);
    }

    private function ownedRequestOrFail(Request $request, SearchRequest $searchRequest): SearchRequest
    {
        return SearchRequest::query()
            ->with('initiator')
            ->whereKey($searchRequest->id)
            ->where('created_by_user_id', $request->user()->id)
            ->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(SearchRequest $item): array
    {
        return [
            'id' => $item->id,
            'search_goal' => $item->search_goal?->value ?? (string) $item->search_goal,
            'status' => $item->status?->value ?? (string) $item->status,
            'initiator_type' => $item->initiator_type,
            'initiator_id' => $item->initiator_id,
            'initiator_label' => $this->initiatorLabel($item),
            'criteria' => $item->criteria ?? [],
            'created_at' => $item->created_at?->toIso8601String(),
            'expires_at' => $item->expires_at?->toIso8601String(),
        ];
    }

    private function initiatorLabel(SearchRequest $request): string
    {
        return match ($request->initiator_type) {
            User::class => 'User: '.($request->initiator?->name ?? '#'.$request->initiator_id),
            Peformer::class => 'Performer: '.($request->initiator?->name ?? '#'.$request->initiator_id),
            Musician::class => 'Musician: '.($request->initiator?->name ?? '#'.$request->initiator_id),
            ConcertVenue::class => 'Venue: '.($request->initiator?->name ?? '#'.$request->initiator_id),
            Studio::class => 'Studio: '.($request->initiator?->name ?? '#'.$request->initiator_id),
            Rehersal::class => 'Rehearsal: '.($request->initiator?->name ?? '#'.$request->initiator_id),
            School::class => 'School: '.($request->initiator?->name ?? '#'.$request->initiator_id),
            RecordLabel::class => 'Label: '.($request->initiator?->name ?? '#'.$request->initiator_id),
            ProducerCenter::class => 'Production: '.($request->initiator?->name ?? '#'.$request->initiator_id),
            Shop::class => 'Shop: '.($request->initiator?->name ?? '#'.$request->initiator_id),
            default => class_basename((string) $request->initiator_type).': #'.$request->initiator_id,
        };
    }

    private function resolveInitiatorType(Request $request): string
    {
        $rawType = $request->input('initiator_type');
        if (is_string($rawType) && $rawType !== '') {
            return $rawType;
        }

        return $request->user()->active_music_actor_type ?? User::class;
    }
}
