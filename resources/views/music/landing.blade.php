@php
    use App\Helpers\LogoHelper;
    $inviteToken = request()->query('invite');
    $canShowRegister = is_string($inviteToken) && app(\App\Services\Auth\RegistrationInviteService::class)->isActiveToken($inviteToken);
@endphp

<x-layouts.public-minimal
    :title="__('ui.music.landing.title')"
    :meta-description="__('ui.music.landing.meta_description')"
    :canonical-url="url()->route('music.landing')"
>
    <div data-music-landing class="relative overflow-hidden bg-zinc-50 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
        <div aria-hidden="true" class="pointer-events-none absolute inset-0 overflow-hidden">
            <div data-landing-parallax="0.14" class="absolute -left-20 top-12 h-72 w-72 rounded-full bg-fuchsia-500/15 blur-3xl"></div>
            <div data-landing-parallax="0.2" class="absolute right-[-120px] top-0 h-96 w-96 rounded-full bg-sky-500/15 blur-3xl"></div>
            <div data-landing-parallax="0.1" class="absolute bottom-[-140px] left-1/3 h-80 w-80 rounded-full bg-violet-500/10 blur-3xl"></div>
        </div>

        <header class="relative z-10 border-b border-zinc-200/80 bg-white/75 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/70">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2">
                    <img
                        src="{{ LogoHelper::getPath() }}"
                        alt="{{ LogoHelper::getAlt() }}"
                        class="{{ LogoHelper::getClass('sidebar') }} h-8 w-8"
                    />
                    <span class="text-sm font-semibold tracking-wide">M-Engine</span>
                </a>
                <nav class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                    <a href="{{ route('discover') }}" class="text-zinc-600 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-zinc-100">
                        {{ __('ui.music.landing.nav_discover') }}
                    </a>
                    @guest
                        <a href="{{ route('login') }}" class="text-zinc-600 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-zinc-100">
                            {{ __('ui.auth.login.button') }}
                        </a>
                        @if (Route::has('register') && $canShowRegister)
                            <a href="{{ route('register', ['invite' => $inviteToken]) }}" class="rounded-md bg-zinc-900 px-3 py-1.5 text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-950 dark:hover:bg-zinc-300">
                                {{ __('ui.auth.register.button') }}
                            </a>
                        @endif
                    @else
                        <a href="{{ route('dashboard') }}" class="rounded-md bg-zinc-900 px-3 py-1.5 text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-950 dark:hover:bg-zinc-300">
                            {{ __('ui.dashboard') }}
                        </a>
                    @endguest
                </nav>
            </div>
        </header>

        <main class="relative z-10 py-6 lg:py-10">
            <section class="mx-auto grid max-w-6xl gap-8 px-4 pb-14 pt-16 sm:px-6 lg:grid-cols-2 lg:gap-16 lg:px-8 lg:pt-24">
                <div class="space-y-6">
                    <p data-landing-fade class="inline-flex rounded-full border border-zinc-300 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-zinc-600 dark:border-zinc-700 dark:text-zinc-300">
                        {{ __('ui.music.landing.hero_badge') }}
                    </p>
                    <h1 data-landing-stagger class="text-balance text-4xl font-semibold leading-tight sm:text-5xl">
                        {{ __('ui.music.landing.hero_title') }}
                    </h1>
                    <p data-landing-stagger class="max-w-xl text-base text-zinc-600 dark:text-zinc-300 sm:text-lg">
                        {{ __('ui.music.landing.hero_subtitle') }}
                    </p>
                    <div data-landing-stagger class="flex flex-wrap gap-3">
                        <a href="{{ route('discover') }}" class="rounded-lg bg-zinc-900 px-5 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-950 dark:hover:bg-zinc-300">
                            {{ __('ui.music.landing.hero_cta_primary') }}
                        </a>
                        @if (Route::has('register') && $canShowRegister)
                            <a href="{{ route('register', ['invite' => $inviteToken]) }}" class="rounded-lg border border-zinc-300 px-5 py-3 text-sm font-semibold transition hover:-translate-y-0.5 hover:border-zinc-500 dark:border-zinc-700 dark:hover:border-zinc-500">
                                {{ __('ui.music.landing.hero_cta_secondary') }}
                            </a>
                        @endif
                    </div>
                </div>
                <div class="relative flex items-center justify-center">
                    <div class="relative h-72 w-72 sm:h-80 sm:w-80">
                        <div data-landing-logo-ring class="absolute inset-0 rounded-full border border-zinc-300/80 dark:border-zinc-700/80"></div>
                        <div data-landing-logo-ring class="absolute inset-6 rounded-full border border-zinc-300/70 dark:border-zinc-700/70"></div>
                        <div data-landing-logo class="absolute inset-0 flex items-center justify-center rounded-full bg-white/80 shadow-2xl shadow-zinc-900/10 dark:bg-zinc-900/90 dark:shadow-black/30">
                            <img
                                src="{{ LogoHelper::getPath() }}"
                                alt="{{ LogoHelper::getAlt() }}"
                                class="{{ LogoHelper::getClass('center') }} h-full w-full object-cover"
                            />
                        </div>
                        <span class="absolute -right-3 top-10 rounded-full border border-zinc-300 bg-white/80 px-3 py-1 text-xs dark:border-zinc-700 dark:bg-zinc-900/80">Live</span>
                        <span class="absolute -left-4 bottom-16 rounded-full border border-zinc-300 bg-white/80 px-3 py-1 text-xs dark:border-zinc-700 dark:bg-zinc-900/80">Discover</span>
                    </div>
                </div>
            </section>

            <section class="mx-auto max-w-6xl px-4 pb-12 sm:px-6 lg:px-8">
                <div data-landing-reveal class="rounded-2xl border border-zinc-200 bg-white/85 p-6 dark:border-zinc-800 dark:bg-zinc-900/75">
                    <h2 class="text-2xl font-semibold">{{ __('ui.music.landing.catalog_title') }}</h2>
                    <p class="mt-2 max-w-2xl text-sm text-zinc-600 dark:text-zinc-300">{{ __('ui.music.landing.catalog_subtitle') }}</p>
                    <div class="swiper mt-6 overflow-hidden" data-landing-catalog-swiper>
                        <div class="swiper-wrapper">
                            @foreach ($discoverCategories as $category)
                                <div class="swiper-slide h-auto">
                                    <a
                                        href="{{ route('discover.category', ['category' => $category]) }}"
                                        data-landing-card
                                        class="group relative block h-full overflow-hidden rounded-xl border border-zinc-200 bg-white p-4 transition dark:border-zinc-800 dark:bg-zinc-900"
                                    >
                                        <p class="text-xs uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                            {{ __('ui.music.discover_type.' . $category) }}
                                        </p>
                                        <p class="mt-1 text-lg font-semibold">
                                            {{ __('ui.music.discover_category.' . $category) }}
                                        </p>
                                        <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">
                                            {{ trans_choice('ui.music.landing.catalog_count', (int) ($catalogCounts[$category] ?? 0), ['count' => $catalogCounts[$category] ?? 0]) }}
                                        </p>
                                        <span class="mt-4 inline-flex text-sm font-medium text-zinc-700 transition group-hover:translate-x-1 dark:text-zinc-200">
                                            {{ __('ui.music.landing.open_category') }} ->
                                        </span>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="mt-5 flex items-center justify-between gap-3">
                        <button type="button" data-landing-catalog-prev class="rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700">←</button>
                        <div data-landing-catalog-pagination class="text-center"></div>
                        <button type="button" data-landing-catalog-next class="rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700">→</button>
                    </div>
                </div>
            </section>

            <section class="mx-auto grid max-w-6xl gap-6 px-4 pb-12 sm:px-6 lg:grid-cols-2 lg:px-8">
                <article data-landing-reveal class="rounded-2xl border border-zinc-200 bg-white/85 p-6 dark:border-zinc-800 dark:bg-zinc-900/75">
                    <h3 class="text-xl font-semibold">{{ __('ui.music.landing.guest_title') }}</h3>
                    <ul class="mt-4 space-y-3 text-sm text-zinc-600 dark:text-zinc-300">
                        <li>{{ __('ui.music.landing.guest_item_directory') }}</li>
                        <li>{{ __('ui.music.landing.guest_item_profiles') }}</li>
                        <li>{{ __('ui.music.landing.guest_item_filters') }}</li>
                    </ul>
                    <a href="{{ route('discover') }}" class="mt-5 inline-flex rounded-lg border border-zinc-300 px-4 py-2 text-sm font-semibold hover:border-zinc-500 dark:border-zinc-700 dark:hover:border-zinc-500">
                        {{ __('ui.music.landing.guest_cta') }}
                    </a>
                </article>
                <article data-landing-reveal class="rounded-2xl border border-zinc-200 bg-white/85 p-6 dark:border-zinc-800 dark:bg-zinc-900/75">
                    <h3 class="text-xl font-semibold">{{ __('ui.music.landing.how_title') }}</h3>
                    <ol class="mt-4 space-y-4 text-sm text-zinc-600 dark:text-zinc-300">
                        <li><strong class="text-zinc-900 dark:text-zinc-100">1.</strong> {{ __('ui.music.landing.how_step_one') }}</li>
                        <li><strong class="text-zinc-900 dark:text-zinc-100">2.</strong> {{ __('ui.music.landing.how_step_two') }}</li>
                        <li><strong class="text-zinc-900 dark:text-zinc-100">3.</strong> {{ __('ui.music.landing.how_step_three') }}</li>
                    </ol>
                </article>
            </section>

            <section data-landing-reveal class="mx-auto max-w-6xl px-4 pb-16 sm:px-6 lg:px-8 lg:pb-24">
                <div class="rounded-2xl border border-zinc-200 bg-gradient-to-r from-violet-500/10 via-fuchsia-500/10 to-cyan-500/10 p-8 dark:border-zinc-800">
                    <h3 class="text-2xl font-semibold">{{ __('ui.music.landing.final_title') }}</h3>
                    <p class="mt-2 max-w-3xl text-sm text-zinc-700 dark:text-zinc-200">{{ __('ui.music.landing.final_subtitle') }}</p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('discover') }}" class="rounded-lg bg-zinc-900 px-5 py-3 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-950 dark:hover:bg-zinc-300">
                            {{ __('ui.music.landing.final_cta_discover') }}
                        </a>
                        @guest
                            <a href="{{ route('login') }}" class="rounded-lg border border-zinc-300 px-5 py-3 text-sm font-semibold hover:border-zinc-500 dark:border-zinc-700 dark:hover:border-zinc-500">
                                {{ __('ui.music.landing.final_cta_login') }}
                            </a>
                            @if (Route::has('register') && $canShowRegister)
                                <a href="{{ route('register', ['invite' => $inviteToken]) }}" class="rounded-lg border border-zinc-300 px-5 py-3 text-sm font-semibold hover:border-zinc-500 dark:border-zinc-700 dark:hover:border-zinc-500">
                                    {{ __('ui.music.landing.final_cta_register') }}
                                </a>
                            @endif
                        @endguest
                    </div>
                </div>
            </section>
        </main>
    </div>

    {!! LogoHelper::generateStyles('sidebar') !!}
    {!! LogoHelper::generateStyles('center') !!}
</x-layouts.public-minimal>
