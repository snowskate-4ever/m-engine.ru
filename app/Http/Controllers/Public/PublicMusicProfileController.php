<?php

namespace App\Http\Controllers\Public;

use App\Enums\PerformerMembershipStatus;
use App\Http\Controllers\Controller;
use App\Models\Musician;
use App\Models\Peformer;
use App\Models\Rehersal;
use App\Models\School;
use App\Models\Studio;
use App\Models\Teacher;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Lang;

class PublicMusicProfileController extends Controller
{
    public function musician(string $slug): View|Response
    {
        $model = Musician::query()
            ->where('slug', $slug)
            ->with([
                'instruments',
                'genres',
                'peformers' => fn ($q) => $q
                    ->wherePivot('status', PerformerMembershipStatus::Accepted->value)
                    ->wherePivot('show_on_musician_profile', true)
                    ->orderBy('name'),
                'addresses' => $this->eagerPublicAddresses(),
            ])
            ->firstOrFail();

        return $this->renderPublic($model, 'ui.public_profile.type_musician', 'public.profiles.musician');
    }

    public function teacher(string $slug): View|Response
    {
        $model = Teacher::query()
            ->where('slug', $slug)
            ->with(['cities', 'addresses' => $this->eagerPublicAddresses()])
            ->firstOrFail();

        return $this->renderPublic($model, 'ui.public_profile.type_teacher', 'public.profiles.simple-entity');
    }

    public function performer(string $slug): View|Response
    {
        $model = Peformer::query()
            ->where('slug', $slug)
            ->with([
                'musicians' => fn ($q) => $q->wherePivot('status', PerformerMembershipStatus::Accepted->value),
                'addresses' => $this->eagerPublicAddresses(),
            ])
            ->firstOrFail();

        return $this->renderPublic($model, 'ui.public_profile.type_performer', 'public.profiles.simple-entity');
    }

    public function studio(string $slug): View|Response
    {
        $model = Studio::query()
            ->where('slug', $slug)
            ->with(['addresses' => $this->eagerPublicAddresses()])
            ->firstOrFail();

        return $this->renderPublic($model, 'ui.public_profile.type_studio', 'public.profiles.simple-entity');
    }

    public function rehearsal(string $slug): View|Response
    {
        $model = Rehersal::query()
            ->where('slug', $slug)
            ->with(['addresses' => $this->eagerPublicAddresses()])
            ->firstOrFail();

        return $this->renderPublic($model, 'ui.public_profile.type_rehearsal', 'public.profiles.simple-entity');
    }

    public function school(string $slug): View|Response
    {
        $model = School::query()
            ->where('slug', $slug)
            ->with(['addresses' => $this->eagerPublicAddresses()])
            ->firstOrFail();

        return $this->renderPublic($model, 'ui.public_profile.type_school', 'public.profiles.simple-entity');
    }

    protected function renderPublic(object $model, string $typeLangKey, string $publicView): View|Response
    {
        $typeLabel = Lang::get($typeLangKey);

        if (! $model->public_page_enabled) {
            return response()->view('public.profiles.hidden', [
                'entityTypeLabel' => $typeLabel,
            ], 200);
        }

        return view($publicView, ['model' => $model]);
    }

    /**
     * @return Closure(Builder): void
     */
    private function eagerPublicAddresses(): Closure
    {
        return function (Builder $q): void {
            $q->where('is_active', true)
                ->where('is_public', true)
                ->with(['country', 'region', 'city'])
                ->orderByDesc('is_primary')
                ->orderBy('id');
        };
    }
}
