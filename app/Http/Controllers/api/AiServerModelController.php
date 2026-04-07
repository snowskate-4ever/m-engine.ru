<?php

declare(strict_types=1);

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\AiServerModel;
use App\Services\Ai\AiServerQuotaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiServerModelController extends Controller
{
    public function __construct(
        private readonly AiServerQuotaService $quota,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $allowed = $user !== null ? $this->quota->allowedServerModelIdsForApi($user) : null;

        $query = AiServerModel::query()
            ->where('is_active', true)
            ->whereHas('provider', fn ($q) => $q->where('is_active', true))
            ->with(['provider:id,name,driver'])
            ->orderBy('sort_order')
            ->orderBy('id');

        if (is_array($allowed)) {
            if ($allowed === []) {
                return response()->json(['data' => []]);
            }
            $query->whereIn('id', $allowed);
        }

        $models = $query->get();

        $data = $models->map(static function (AiServerModel $m) {
            return [
                'id' => $m->id,
                'display_name' => $m->display_name,
                'vendor_model_id' => $m->vendor_model_id,
                'provider' => [
                    'id' => $m->provider->id,
                    'name' => $m->provider->name,
                    'driver' => $m->provider->driver,
                ],
            ];
        });

        return response()->json(['data' => $data]);
    }
}
