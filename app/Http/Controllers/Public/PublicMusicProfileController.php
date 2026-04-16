<?php

namespace App\Http\Controllers\Public;

use App\Enums\PerformerMembershipStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ConcertVenue;
use App\Models\Musician;
use App\Models\Peformer;
use App\Models\ProducerCenter;
use App\Models\RecordLabel;
use App\Models\Rehersal;
use App\Models\School;
use App\Models\Shop;
use App\Models\Studio;
use App\Models\Teacher;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Lang;

class PublicMusicProfileController extends Controller
{
    public function show(Request $request, string $slug): View|Response
    {
        $musician = Musician::query()
            ->where('slug', $slug)
            ->with([
                'instruments',
                'genres',
                'cities',
                'peformers' => fn ($q) => $q
                    ->wherePivot('status', PerformerMembershipStatus::Accepted->value)
                    ->wherePivot('show_on_musician_profile', true)
                    ->orderBy('name'),
                'addresses' => $this->eagerPublicAddresses(),
                'socials' => $this->eagerPublicSocials(),
            ])
            ->first();
        if ($musician) {
            return $this->renderPublic($musician, 'ui.public_profile.type_musician', 'public.profiles.musician');
        }

        $teacher = Teacher::query()
            ->where('slug', $slug)
            ->with(['cities', 'addresses' => $this->eagerPublicAddresses(), 'socials' => $this->eagerPublicSocials()])
            ->first();
        if ($teacher) {
            return $this->renderPublic($teacher, 'ui.public_profile.type_teacher', 'public.profiles.simple-entity');
        }

        $performer = Peformer::query()
            ->where('slug', $slug)
            ->with([
                'musicians' => fn ($q) => $q->wherePivot('status', PerformerMembershipStatus::Accepted->value),
                'addresses' => $this->eagerPublicAddresses(),
                'socials' => $this->eagerPublicSocials(),
            ])
            ->first();
        if ($performer) {
            return $this->renderPublic($performer, 'ui.public_profile.type_performer', 'public.profiles.simple-entity');
        }

        $simpleEntityLoader = function (string $className) use ($slug): ?object {
            return $className::query()
                ->where('slug', $slug)
                ->with(['addresses' => $this->eagerPublicAddresses(), 'socials' => $this->eagerPublicSocials()])
                ->first();
        };

        $studio = $simpleEntityLoader(Studio::class);
        if ($studio) {
            return $this->renderPublic($studio, 'ui.public_profile.type_studio', 'public.profiles.simple-entity');
        }
        $rehearsal = $simpleEntityLoader(Rehersal::class);
        if ($rehearsal) {
            return $this->renderPublic($rehearsal, 'ui.public_profile.type_rehearsal', 'public.profiles.simple-entity');
        }
        $concertVenue = $simpleEntityLoader(ConcertVenue::class);
        if ($concertVenue) {
            return $this->renderPublic($concertVenue, 'ui.public_profile.type_concert_venue', 'public.profiles.simple-entity');
        }
        $school = $simpleEntityLoader(School::class);
        if ($school) {
            return $this->renderPublic($school, 'ui.public_profile.type_school', 'public.profiles.simple-entity');
        }
        $recordLabel = $simpleEntityLoader(RecordLabel::class);
        if ($recordLabel) {
            return $this->renderPublic($recordLabel, 'ui.public_profile.type_record_label', 'public.profiles.simple-entity');
        }
        $producerCenter = $simpleEntityLoader(ProducerCenter::class);
        if ($producerCenter) {
            return $this->renderPublic($producerCenter, 'ui.public_profile.type_producer_center', 'public.profiles.simple-entity');
        }

        $shop = Shop::query()->where('slug', $slug)->first();
        if ($shop) {
            return $this->shop($request, $slug);
        }

        abort(404);
    }

    public function musician(string $slug): View|Response
    {
        $model = Musician::query()
            ->where('slug', $slug)
            ->with([
                'instruments',
                'genres',
                'cities',
                'peformers' => fn ($q) => $q
                    ->wherePivot('status', PerformerMembershipStatus::Accepted->value)
                    ->wherePivot('show_on_musician_profile', true)
                    ->orderBy('name'),
                'addresses' => $this->eagerPublicAddresses(),
                'socials' => $this->eagerPublicSocials(),
            ])
            ->firstOrFail();

        return $this->renderPublic($model, 'ui.public_profile.type_musician', 'public.profiles.musician');
    }

    public function teacher(string $slug): View|Response
    {
        $model = Teacher::query()
            ->where('slug', $slug)
            ->with(['cities', 'addresses' => $this->eagerPublicAddresses(), 'socials' => $this->eagerPublicSocials()])
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
                'socials' => $this->eagerPublicSocials(),
            ])
            ->firstOrFail();

        return $this->renderPublic($model, 'ui.public_profile.type_performer', 'public.profiles.simple-entity');
    }

    public function studio(string $slug): View|Response
    {
        $model = Studio::query()
            ->where('slug', $slug)
            ->with(['addresses' => $this->eagerPublicAddresses(), 'socials' => $this->eagerPublicSocials()])
            ->firstOrFail();

        return $this->renderPublic($model, 'ui.public_profile.type_studio', 'public.profiles.simple-entity');
    }

    public function rehearsal(string $slug): View|Response
    {
        $model = Rehersal::query()
            ->where('slug', $slug)
            ->with(['addresses' => $this->eagerPublicAddresses(), 'socials' => $this->eagerPublicSocials()])
            ->firstOrFail();

        return $this->renderPublic($model, 'ui.public_profile.type_rehearsal', 'public.profiles.simple-entity');
    }

    public function concertVenue(string $slug): View|Response
    {
        $model = ConcertVenue::query()
            ->where('slug', $slug)
            ->with(['addresses' => $this->eagerPublicAddresses(), 'socials' => $this->eagerPublicSocials()])
            ->firstOrFail();

        return $this->renderPublic($model, 'ui.public_profile.type_concert_venue', 'public.profiles.simple-entity');
    }

    public function school(string $slug): View|Response
    {
        $model = School::query()
            ->where('slug', $slug)
            ->with(['addresses' => $this->eagerPublicAddresses(), 'socials' => $this->eagerPublicSocials()])
            ->firstOrFail();

        return $this->renderPublic($model, 'ui.public_profile.type_school', 'public.profiles.simple-entity');
    }

    public function recordLabel(string $slug): View|Response
    {
        $model = RecordLabel::query()
            ->where('slug', $slug)
            ->with(['addresses' => $this->eagerPublicAddresses(), 'socials' => $this->eagerPublicSocials()])
            ->firstOrFail();

        return $this->renderPublic($model, 'ui.public_profile.type_record_label', 'public.profiles.simple-entity');
    }

    public function producerCenter(string $slug): View|Response
    {
        $model = ProducerCenter::query()
            ->where('slug', $slug)
            ->with(['addresses' => $this->eagerPublicAddresses(), 'socials' => $this->eagerPublicSocials()])
            ->firstOrFail();

        return $this->renderPublic($model, 'ui.public_profile.type_producer_center', 'public.profiles.simple-entity');
    }

    public function shop(Request $request, string $slug): View|Response
    {
        $model = Shop::query()->where('slug', $slug)->firstOrFail();

        $listingCategories = Category::query()
            ->whereHas('goods.shopItems', fn ($q) => $q->where('shop_id', $model->id))
            ->orderBy('name')
            ->get();

        $requestedCategoryId = (int) $request->query('category', 0);
        $allowedIds = $listingCategories->pluck('id')->all();
        $listingCategoryId = $requestedCategoryId > 0 && in_array($requestedCategoryId, $allowedIds, true)
            ? $requestedCategoryId
            : 0;

        $model->load([
            'addresses' => $this->eagerPublicAddresses(),
            'socials' => $this->eagerPublicSocials(),
            'items' => fn ($q) => $q
                ->when(
                    $listingCategoryId > 0,
                    fn ($q) => $q->whereHas('good.categories', fn ($qq) => $qq->where('categories.id', $listingCategoryId))
                )
                ->with(['good.categories', 'images'])
                ->orderBy('code'),
        ]);

        return $this->renderPublic($model, 'ui.public_profile.type_shop', 'public.profiles.simple-entity', [
            'shopListingCategoryId' => $listingCategoryId,
            'shopListingCategories' => $listingCategories,
        ]);
    }

    protected function renderPublic(object $model, string $typeLangKey, string $publicView, array $extraViewData = []): View|Response
    {
        $typeLabel = Lang::get($typeLangKey);

        if ($this->isModerationBlocked($model)) {
            return response()->view('public.profiles.moderation_hidden', [
                'entityTypeLabel' => $typeLabel,
            ], 200);
        }

        if (! $model->public_page_enabled) {
            return response()->view('public.profiles.hidden', [
                'entityTypeLabel' => $typeLabel,
            ], 200);
        }

        $overlay = method_exists($model, 'publicModerationOverlay')
            ? $model->publicModerationOverlay()
            : null;
        if ($overlay !== null) {
            return response()->view('public.profiles.moderation_status', [
                'entityTypeLabel' => $typeLabel,
                'overlay' => $overlay,
            ], 200);
        }

        return view($publicView, array_merge(['model' => $model], $extraViewData));
    }

    private function isModerationBlocked(object $model): bool
    {
        return is_object($model)
            && method_exists($model, 'isModerationHidden')
            && $model->isModerationHidden();
    }

    /**
     * @return Closure(Relation): void
     */
    private function eagerPublicAddresses(): Closure
    {
        return function (Relation $relation): void {
            $relation->where('is_active', true)
                ->where('is_public', true)
                ->with(['country', 'region', 'city'])
                ->orderByDesc('is_primary')
                ->orderBy('id');
        };
    }

    /**
     * @return Closure(Relation): void
     */
    private function eagerPublicSocials(): Closure
    {
        return function (Relation $relation): void {
            $relation->where('active', true)
                ->orderBy('sort_order')
                ->orderBy('id');
        };
    }
}
