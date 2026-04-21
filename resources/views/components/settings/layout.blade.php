{{-- Карточка контента: отступы как на /music/search-requests (p-5 / md:p-6) --}}
<div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-700 dark:bg-zinc-900 md:p-6">
    @if (filled($subheading ?? null))
        <p
            class="mb-6 text-base leading-relaxed text-zinc-600 dark:text-zinc-400"
            data-test="settings-subheading"
        >
            {{ $subheading }}
        </p>
    @endif

    <div class="w-full max-w-2xl space-y-8">
        {{ $slot }}
    </div>
</div>
