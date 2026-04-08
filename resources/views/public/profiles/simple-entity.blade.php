{{-- Публичная страница с поддержкой блоков layout_published (teacher, performer, studio, …) --}}
@php
    use App\Enums\LegalEntityType;
    use App\Models\Peformer;
    use App\Models\Rehersal;
    use App\Models\School;
    use App\Models\Studio;
    use App\Models\Teacher;

    $canLayout = method_exists($model, 'shouldShowPublicBlock');
    $show = fn (string $id): bool => ! $canLayout || $model->shouldShowPublicBlock($id);
    $hasLegal = $model instanceof Teacher || $model instanceof Studio || $model instanceof Rehersal || $model instanceof School;
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
    </article>
</x-layouts.public-minimal>
