{{-- Публичная страница с поддержкой блоков layout_published (teacher, performer, studio, …) --}}
@php
    use App\Enums\LegalDocumentStatus;
    use App\Enums\LegalDocumentVisibility;
    use App\Enums\LegalEntityType;
    use App\Models\ConcertVenue;
    use App\Models\Peformer;
    use App\Models\ProducerCenter;
    use App\Models\RecordLabel;
    use App\Models\Rehersal;
    use App\Models\School;
    use App\Models\Shop;
    use App\Models\Studio;
    use App\Models\Teacher;
    use Illuminate\Support\Facades\URL;

    $canLayout = method_exists($model, 'shouldShowPublicBlock');
    $show = fn (string $id): bool => ! $canLayout || $model->shouldShowPublicBlock($id);
    $hasLegal = $model instanceof Teacher || $model instanceof Studio || $model instanceof Rehersal || $model instanceof ConcertVenue || $model instanceof School || $model instanceof RecordLabel || $model instanceof ProducerCenter || $model instanceof Shop;
    $publicLegalDocuments = collect();
    if (method_exists($model, 'legalDocuments') && $model->relationLoaded('legalDocuments')) {
        $publicLegalDocuments = $model->legalDocuments
            ->filter(fn ($doc) => ($doc->status?->value ?? null) === LegalDocumentStatus::Approved->value && ($doc->visibility?->value ?? null) === LegalDocumentVisibility::Public->value)
            ->values();
    }
@endphp
<x-layouts.public-minimal :title="$model->name">
    <article class="mx-auto max-w-2xl px-6 py-12">
        @if($show('header'))
            <header class="border-b border-zinc-200 pb-8 dark:border-zinc-800">
                <h1 class="text-2xl font-semibold tracking-tight">{{ $model->name }}</h1>
            </header>
        @endif
        @if($show('description') && filled($model->description))
            <div class="prose prose-zinc mt-8 max-w-none text-sm dark:prose-invert">
                <p class="whitespace-pre-wrap">{{ $model->description }}</p>
            </div>
        @endif
        @if($model instanceof Peformer && $show('members') && $model->musicians->isNotEmpty())
            <section class="mt-8">
                <h2 class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('ui.music.blocks.members') }}</h2>
                <ul class="mt-3 flex flex-wrap gap-2">
                    @foreach($model->musicians as $musician)
                        <li class="rounded-md border border-zinc-200 px-2.5 py-1 text-xs text-zinc-700 dark:border-zinc-700 dark:text-zinc-300">{{ $musician->name }}</li>
                    @endforeach
                </ul>
            </section>
        @endif
        @if($model instanceof Shop && $show('listings'))
            @isset($shopListingCategories)
                @if($shopListingCategories->isNotEmpty())
                    <nav class="mt-10" aria-label="{{ __('ui.music.blocks.listings') }}">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('ui.music.shop_listing_filter_hint') }}</p>
                        <ul class="mt-3 flex flex-wrap gap-2">
                            <li>
                                <a
                                    href="{{ route('public.profile.show', ['slug' => $model->slug]) }}"
                                    @class([
                                        'rounded-full px-3 py-1 text-xs font-medium ring-1 transition',
                                        'bg-zinc-900 text-white ring-zinc-900 dark:bg-zinc-100 dark:text-zinc-900 dark:ring-zinc-100' => ($shopListingCategoryId ?? 0) === 0,
                                        'bg-zinc-100 text-zinc-700 ring-zinc-200 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-200 dark:ring-zinc-700 dark:hover:bg-zinc-700' => ($shopListingCategoryId ?? 0) !== 0,
                                    ])
                                >{{ __('ui.music.shop_listing_all_categories') }}</a>
                            </li>
                            @foreach($shopListingCategories as $cat)
                                <li>
                                    <a
                                        href="{{ route('public.profile.show', ['slug' => $model->slug, 'category' => $cat->id]) }}"
                                        @class([
                                            'rounded-full px-3 py-1 text-xs font-medium ring-1 transition',
                                            'bg-zinc-900 text-white ring-zinc-900 dark:bg-zinc-100 dark:text-zinc-900 dark:ring-zinc-100' => (int) ($shopListingCategoryId ?? 0) === (int) $cat->id,
                                            'bg-zinc-100 text-zinc-700 ring-zinc-200 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-200 dark:ring-zinc-700 dark:hover:bg-zinc-700' => (int) ($shopListingCategoryId ?? 0) !== (int) $cat->id,
                                        ])
                                    >{{ $cat->name ?: '—' }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </nav>
                @endif
            @endisset
            @if($model->items->isNotEmpty())
            <section class="mt-10" aria-label="{{ __('ui.music.blocks.listings') }}">
                <h2 class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('ui.music.blocks.listings') }}</h2>
                <ul class="mt-4 grid list-none grid-cols-1 gap-4 sm:grid-cols-2">
                    @foreach($model->items as $item)
                        <li class="flex gap-3 rounded-xl border border-zinc-200 bg-zinc-50/80 p-3 dark:border-zinc-700 dark:bg-zinc-900/50">
                            <div class="size-20 shrink-0 overflow-hidden rounded-lg bg-zinc-200 dark:bg-zinc-800">
                                @if($url = $item->publicPrimaryImageUrl())
                                    <img src="{{ $url }}" alt="" class="size-full object-cover" loading="lazy" />
                                @endif
                            </div>
                            <div class="min-w-0 flex-1 space-y-1 text-sm">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $item->displayTitle() }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $item->code }}</div>
                                @if($item->good && $item->good->categories->isNotEmpty())
                                    <ul class="flex flex-wrap gap-1.5 pt-0.5">
                                        @foreach($item->good->categories as $c)
                                            <li class="rounded border border-zinc-200 px-1.5 py-0.5 text-[10px] uppercase tracking-wide text-zinc-600 dark:border-zinc-600 dark:text-zinc-400">{{ $c->name ?: '—' }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                                @if($d = $item->displayDescription())
                                    <p class="line-clamp-2 text-xs text-zinc-600 dark:text-zinc-300">{{ \Illuminate\Support\Str::limit(strip_tags($d), 140) }}</p>
                                @endif
                                <div class="flex flex-wrap gap-x-3 gap-y-1 pt-1 text-xs text-zinc-600 dark:text-zinc-400">
                                    <span>{{ __('ui.music.shop_inventory_price') }}: <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $item->price }}</span></span>
                                    @if($item->stock_quantity > 0)
                                        <span>{{ __('ui.music.shop_public_in_stock', ['n' => $item->stock_quantity]) }}</span>
                                    @else
                                        <span class="text-amber-700 dark:text-amber-400">{{ __('ui.music.shop_public_out_of_stock') }}</span>
                                    @endif
                                </div>
                                <livewire:music.add-to-shop-cart :shop-item-id="$item->id" :key="'cart-'.$item->id" />
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
            @elseif(isset($shopListingCategories) && $shopListingCategories->isNotEmpty() && ($shopListingCategoryId ?? 0) > 0)
                <p class="mt-10 text-sm text-zinc-500 dark:text-zinc-400">{{ __('ui.music.shop_listing_no_match') }}</p>
            @endif
        @endif
        @if($hasLegal && $show('legal') && ($model->company_name || $model->inn || $model->ogrn || $model->legal_entity_type))
            <section class="mt-8 rounded-lg border border-zinc-200 p-4 text-sm dark:border-zinc-700">
                <h2 class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('ui.music.blocks.legal') }}</h2>
                <dl class="mt-3 space-y-1 text-zinc-700 dark:text-zinc-300">
                    @if($model->legal_entity_type)
                        <div><span class="font-medium">{{ __('ui.music.fields.legal_entity_type') }}:</span> {{ $model->legal_entity_type === LegalEntityType::LegalPerson ? __('ui.music.legal_person') : __('ui.music.legal_individual') }}</div>
                    @endif
                    @if(filled($model->company_name))
                        <div><span class="font-medium">{{ __('ui.music.fields.company_name') }}:</span> {{ $model->company_name }}</div>
                    @endif
                    @if(filled($model->inn))
                        <div><span class="font-medium">{{ __('ui.music.fields.inn') }}:</span> {{ $model->inn }}</div>
                    @endif
                    @if(filled($model->ogrn))
                        <div><span class="font-medium">{{ __('ui.music.fields.ogrn') }}:</span> {{ $model->ogrn }}</div>
                    @endif
                </dl>
            </section>
        @endif
        @if($hasLegal && $show('legal_documents') && $publicLegalDocuments->isNotEmpty())
            <section class="mt-8 rounded-lg border border-zinc-200 p-4 text-sm dark:border-zinc-700">
                <h2 class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('ui.music.blocks.legal_documents') }}</h2>
                <ul class="mt-3 space-y-2 text-zinc-700 dark:text-zinc-300">
                    @foreach($publicLegalDocuments as $document)
                        <li class="rounded-md border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                            <p class="font-medium text-zinc-800 dark:text-zinc-100">{{ $document->title }}</p>
                            @if($document->currentVersion?->effective_from)
                                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ __('ui.legal_documents.field_effective_from') }}: {{ $document->currentVersion->effective_from->format('Y-m-d') }}
                                </p>
                            @endif
                            @if(filled($document->currentVersion?->external_url))
                                <a href="{{ $document->currentVersion->external_url }}" target="_blank" rel="noopener noreferrer nofollow" class="mt-1 inline-block underline underline-offset-2">
                                    {{ __('ui.open') }}
                                </a>
                            @elseif(filled($document->currentVersion?->file_path))
                                <a
                                    href="{{ URL::temporarySignedRoute('public.legal-document.download', now()->addMinutes(60), ['version' => $document->currentVersion->id]) }}"
                                    class="mt-1 inline-block underline underline-offset-2"
                                >
                                    {{ __('ui.download') }}
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
        @if($show('addresses') && $model->addresses->isNotEmpty())
            <section class="mt-8">
                <h2 class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('ui.music.blocks.addresses') }}</h2>
                <ul class="mt-3 space-y-2 text-sm text-zinc-700 dark:text-zinc-300">
                    @foreach($model->addresses as $addr)
                        <li>
                            @if(filled($addr->name))
                                <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $addr->name }}</span>
                                <span class="text-zinc-500"> — </span>
                            @endif
                            {{ $addr->full_address }}
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
        @if($show('links') && $model->socials->isNotEmpty())
            <section class="mt-8">
                <h2 class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('ui.music.blocks.links') }}</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach($model->socials as $social)
                        <li>
                            <a
                                href="{{ $social->link }}"
                                target="_blank"
                                rel="noopener noreferrer nofollow"
                                class="font-medium text-zinc-800 underline underline-offset-2 dark:text-zinc-200"
                            >
                                {{ $social->name ?: __('ui.social.types.'.($social->type ?: 'other')) }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
        @if($model instanceof Teacher && $show('cities') && ($model->available_other_cities || $model->cities->isNotEmpty()))
            <section class="mt-8">
                <h2 class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('ui.music.blocks.cities') }}</h2>
                @if($model->cities->isNotEmpty())
                    <ul class="mt-3 flex flex-wrap gap-2">
                        @foreach($model->cities as $city)
                            <li class="rounded-md border border-zinc-200 px-2.5 py-1 text-xs text-zinc-700 dark:border-zinc-700 dark:text-zinc-300">{{ $city->name }}</li>
                        @endforeach
                    </ul>
                @elseif($model->available_other_cities)
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ __('ui.music.cities_other_only') }}</p>
                @endif
            </section>
        @endif

        @include('public.profiles.partials.report-profile-form', ['model' => $model])
    </article>
</x-layouts.public-minimal>
