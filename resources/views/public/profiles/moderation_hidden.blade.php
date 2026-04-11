<x-layouts.public-minimal :title="__('ui.public_profile.moderation_hidden_title')">
    <main class="mx-auto flex min-h-svh max-w-lg flex-col items-center justify-center px-6 py-16 text-center">
        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ $entityTypeLabel ?? '' }}</p>
        <h1 class="mt-2 text-xl font-semibold tracking-tight">{{ __('ui.public_profile.moderation_hidden_heading') }}</h1>
        <p class="mt-4 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">
            {{ __('ui.public_profile.moderation_hidden_body') }}
        </p>
        <a href="{{ route('home') }}" class="mt-8 text-sm font-medium text-zinc-900 underline underline-offset-4 hover:text-zinc-700 dark:text-zinc-100 dark:hover:text-zinc-300">
            {{ __('ui.home') }}
        </a>
    </main>
</x-layouts.public-minimal>
