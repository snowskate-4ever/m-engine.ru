<?php

declare(strict_types=1);

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class MusicResourceCatalogController extends Controller
{
    public function catalog(Request $request): JsonResponse
    {
        $user = $request->user();

        $sections = [
            $this->section('performers', 'Исполнители', $user->ownedPeformers()->orderBy('name')->get(['id', 'name'])),
            $this->section('studios', 'Студии', $user->ownedStudios()->orderBy('name')->get(['id', 'name'])),
            $this->section('rehearsals', 'Репточки', $user->ownedRehearsals()->orderBy('name')->get(['id', 'name'])),
            $this->section('concert_venues', 'Площадки', $user->ownedConcertVenues()->orderBy('name')->get(['id', 'name'])),
            $this->section('schools', 'Школы', $user->ownedSchools()->orderBy('name')->get(['id', 'name'])),
            $this->section('record_labels', 'Лейблы', $user->ownedRecordLabels()->orderBy('name')->get(['id', 'name'])),
            $this->section('producer_centers', 'Продюсерские центры', $user->ownedProducerCenters()->orderBy('name')->get(['id', 'name'])),
            $this->section('shops', 'Магазины', $user->ownedShops()->orderBy('name')->get(['id', 'name'])),
        ];

        return response()->json([
            'data' => $sections,
        ]);
    }

    /**
     * @param  Collection<int, object{id:int,name:string|null}>  $items
     * @return array<string, mixed>
     */
    private function section(string $key, string $label, Collection $items): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'total_count' => $items->count(),
            'items' => $items->take(10)->map(static fn (object $item): array => [
                'id' => (int) $item->id,
                'name' => (string) ($item->name ?? ('#'.$item->id)),
            ])->values(),
        ];
    }
}
