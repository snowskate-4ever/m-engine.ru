<x-layouts.second_level_layout :title="__('ui.resources')">
    <livewire:components.left-sidebar />

    <div class="p-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <h2 class="mb-3 text-lg font-semibold text-zinc-900 dark:text-zinc-50">{{ __('ui.resources') }}</h2>
            @if($resources->isEmpty())
                <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('ui.notfound') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-zinc-500">
                            <tr>
                                <th class="px-2 py-1">ID</th>
                                <th class="px-2 py-1">{{ __('ui.name') ?? 'Name' }}</th>
                                <th class="px-2 py-1">{{ __('ui.description') ?? 'Description' }}</th>
                                <th class="px-2 py-1">Active</th>
                                <th class="px-2 py-1">Type</th>
                                <th class="px-2 py-1">Start</th>
                                <th class="px-2 py-1">End</th>
                                <th class="px-2 py-1">Create</th>
                                <th class="px-2 py-1">Update</th>
                            </tr>
                        </thead>
                        <tbody class="text-zinc-900 dark:text-zinc-100">
                            @foreach($resources as $resource)
                                <tr class="border-t border-zinc-200 dark:border-zinc-700">
                                    <td class="px-2 py-1">{{ $resource['id'] }}</td>
                                    <td class="px-2 py-1">{{ $resource['name'] }}</td>
                                    <td class="px-2 py-1">{{ $resource['description'] }}</td>
                                    <td class="px-2 py-1">{{ $resource['active'] ? 'Yes' : 'No' }}</td>
                                    <td class="px-2 py-1">{{ $resource['type_name'] }}</td>
                                    <td class="px-2 py-1">{{ $resource['start_at'] }}</td>
                                    <td class="px-2 py-1">{{ $resource['end_at'] }}</td>
                                    <td class="px-2 py-1">{{ $resource['created_at'] }}</td>
                                    <td class="px-2 py-1">{{ $resource['updated_at'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-layouts.second_level_layout>
