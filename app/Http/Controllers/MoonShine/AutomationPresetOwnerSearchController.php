<?php

declare(strict_types=1);

namespace App\Http\Controllers\MoonShine;

use App\Http\Controllers\Controller;
use App\Support\Admin\AutomationPresetOwnerLookup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MoonShine\Laravel\MoonShineAuth;

final class AutomationPresetOwnerSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $moonshineUser = MoonShineAuth::getGuard()->user();
        if ($moonshineUser === null || ! $this->allowsAsyncSearch($moonshineUser)) {
            abort(403);
        }

        $ownerType = $this->mergedOwnerType($request);
        if (! is_string($ownerType) || $ownerType === '' || ! in_array($ownerType, AutomationPresetOwnerLookup::allowedTypes(), true)) {
            return response()->json([]);
        }

        $term = is_scalar($request->input('query')) ? trim((string) $request->input('query')) : '';
        $offset = max(0, (int) $request->input('offset', 0));
        $limit = 15;

        $ensureId = null;
        $rawOwnerId = $this->mergedOwnerId($request);
        if (is_numeric($rawOwnerId)) {
            $ensureId = (int) $rawOwnerId;
        }

        $options = AutomationPresetOwnerLookup::search($ownerType, $term, $offset, $limit, $ensureId);

        return response()->json($options);
    }

    private function mergedOwnerType(Request $request): mixed
    {
        foreach ($request->all() as $value) {
            if (is_array($value) && array_key_exists('owner_type', $value)) {
                return $value['owner_type'];
            }
        }

        return $request->input('owner_type');
    }

    private function mergedOwnerId(Request $request): mixed
    {
        foreach ($request->all() as $value) {
            if (is_array($value) && array_key_exists('owner_id', $value)) {
                return $value['owner_id'];
            }
        }

        return $request->input('owner_id');
    }

    private function allowsAsyncSearch(object $moonshineUser): bool
    {
        if (method_exists($moonshineUser, 'hasRole')) {
            return $moonshineUser->hasRole('admin') || $moonshineUser->hasRole('editor');
        }

        if (method_exists($moonshineUser, 'isSuperUser')) {
            return $moonshineUser->isSuperUser();
        }

        return false;
    }
}
