<x-layouts.second_level_layout :title="__('ui.events')">
    <livewire:components.left-sidebar />

    <div class="p-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <h2 class="mb-3 text-lg font-semibold text-zinc-900 dark:text-zinc-50">{{ __('ui.events') }}</h2>
            @if($events->isEmpty())
                <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('ui.notfound') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-zinc-500">
                            <tr>
                                <th class="px-2 py-1">ID</th>
                                <th class="px-2 py-1">{{ __('ui.name') ?? 'Name' }}</th>
                                <th class="px-2 py-1">{{ __('ui.description') ?? 'Description' }}</th>
                                <th class="px-2 py-1">Start</th>
                                <th class="px-2 py-1">End</th>
                                <th class="px-2 py-1">Active</th>
                            </tr>
                        </thead>
                        <tbody class="text-zinc-900 dark:text-zinc-100">
                            @foreach($events as $event)
                                <tr class="border-t border-zinc-200 dark:border-zinc-700">
                                    <td class="px-2 py-1">{{ $event['id'] }}</td>
                                    <td class="px-2 py-1">{{ $event['name'] }}</td>
                                    <td class="px-2 py-1">{{ $event['description'] }}</td>
                                    <td class="px-2 py-1">{{ $event['start_at'] }}</td>
                                    <td class="px-2 py-1">{{ $event['end_at'] }}</td>
                                    <td class="px-2 py-1">{{ $event['active'] ? 'Yes' : 'No' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-layouts.second_level_layout>

