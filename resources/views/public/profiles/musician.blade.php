<x-layouts.public-minimal :title="$model->name">
    <article class="mx-auto max-w-2xl px-6 py-12">
        @if($model->shouldShowPublicBlock('header'))
            <header class="border-b border-zinc-200 pb-8 dark:border-zinc-800">
                <h1 class="text-2xl font-semibold tracking-tight">{{ $model->name }}</h1>
            </header>
        @endif
        @if($model->shouldShowPublicBlock('bio'))
            <div class="mt-8 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">
                @if(filled($model->bio))
                    <p class="whitespace-pre-wrap">{{ $model->bio }}</p>
                @elseif(filled($model->description))
                    <p class="whitespace-pre-wrap">{{ $model->description }}</p>
                @endif
            </div>
        @endif
        @if($model->shouldShowPublicBlock('instruments') && $model->instruments->isNotEmpty())
            <section class="mt-8">
                <h2 class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('ui.public_profile.musician_instruments') }}</h2>
                <ul class="mt-3 flex flex-wrap gap-2">
                    @foreach($model->instruments as $instrument)
                        <li class="rounded-md bg-zinc-200/70 px-2.5 py-1 text-xs font-medium text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200">
                            {{ $instrument->name }}
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
        @if($model->shouldShowPublicBlock('genres') && $model->genres->isNotEmpty())
            <section class="mt-8">
                <h2 class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('ui.public_profile.musician_genres') }}</h2>
                <ul class="mt-3 flex flex-wrap gap-2">
                    @foreach($model->genres as $genre)
                        <li class="rounded-md border border-zinc-200 px-2.5 py-1 text-xs text-zinc-700 dark:border-zinc-700 dark:text-zinc-300">
                            {{ $genre->name }}
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
        @if($model->shouldShowPublicBlock('addresses') && $model->addresses->isNotEmpty())
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
        @if($model->shouldShowPublicBlock('performers') && $model->peformers->isNotEmpty())
            <section class="mt-8">
                <h2 class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('ui.public_profile.musician_performers') }}</h2>
                <ul class="mt-3 space-y-2">
                    @foreach($model->peformers as $peformer)
                        <li class="text-sm text-zinc-800 dark:text-zinc-200">
                            @if($peformer->public_page_enabled && filled($peformer->slug))
                                <a href="{{ route('public.performers.show', ['slug' => $peformer->slug]) }}" class="font-medium underline-offset-2 hover:underline">
                                    {{ $peformer->name }}
                                </a>
                            @else
                                {{ $peformer->name }}
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @include('public.profiles.partials.report-profile-form', ['model' => $model])
    </article>
</x-layouts.public-minimal>
