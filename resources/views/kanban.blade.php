<x-layouts.second_level_layout :title="__('ui.kanban.title')" :buttons="[]">
    <div class="min-w-0 flex-1">
        <livewire:kanban.kanban-workspace />
    </div>
    @push('scripts')
        @vite('resources/js/kanban-board.js')
    @endpush
</x-layouts.second_level_layout>
