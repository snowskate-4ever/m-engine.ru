<?php

declare(strict_types=1);

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\User;
use App\Services\Contracts\ContractService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ContractApiController extends Controller
{
    public function __construct(
        private readonly ContractService $contracts,
    ) {}

    public function generate(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $validated = $request->validate([
            'template_slug' => ['required', 'string', 'max:64'],
            'party_b_user_id' => ['required', 'integer', 'exists:users,id'],
            'variables' => ['nullable', 'array'],
        ]);

        $template = ContractTemplate::query()->where('slug', $validated['template_slug'])->where('is_active', true)->firstOrFail();
        $version = $template->latestVersion();
        abort_unless($version !== null, 422, 'Template has no versions.');

        /** @var User $partyB */
        $partyB = User::query()->findOrFail((int) $validated['party_b_user_id']);
        $partyA = $user;

        $contract = $this->contracts->generateFromVersion(
            $version,
            $partyA,
            $partyB,
            array_map(static fn ($v) => is_scalar($v) ? (string) $v : '', $validated['variables'] ?? []),
        );

        return response()->json([
            'id' => $contract->id,
            'status' => $contract->status,
            'rendered_body' => $contract->rendered_body,
        ], 201);
    }

    public function accept(Request $request, Contract $contract): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $validated = $request->validate([
            'side' => ['required', 'in:a,b'],
        ]);

        if ($validated['side'] === 'a') {
            abort_unless($this->isPartyUser($contract, 'a', $user), 403);
            $contract = $this->contracts->acceptPartyA($contract, $user);
        } else {
            abort_unless($this->isPartyUser($contract, 'b', $user), 403);
            $contract = $this->contracts->acceptPartyB($contract, $user);
        }

        return response()->json([
            'id' => $contract->id,
            'status' => $contract->status,
            'party_a_accepted_at' => $contract->party_a_accepted_at,
            'party_b_accepted_at' => $contract->party_b_accepted_at,
        ]);
    }

    private function isPartyUser(Contract $contract, string $side, User $user): bool
    {
        if ($side === 'a') {
            return $contract->party_a_type === User::class
                && (int) $contract->party_a_id === (int) $user->id;
        }

        return $contract->party_b_type === User::class
            && (int) $contract->party_b_id === (int) $user->id;
    }
}
