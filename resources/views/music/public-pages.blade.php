<x-layouts.second_level_layout
    :title="__('ui.music.public_pages_page_title')"
    :buttons="[]"
    :content-right-inset="false"
>
    <div class="mx-auto min-w-0 w-full max-w-3xl space-y-6">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="mb-4 text-base font-semibold text-zinc-900 dark:text-zinc-100">
                {{ __('ui.music.public_pages_entity_modal_title') }}
            </h2>
            <livewire:music.public-page-settings-modal wire:key="page-public-pages" panel="public_pages" />
        </div>
    </div>
</x-layouts.second_level_layout>
