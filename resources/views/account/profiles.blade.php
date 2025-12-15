<x-layouts.second_level_layout :title="__('ui.profiles')">
    <livewire:components.left-sidebar />

    <div class="p-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <h2 class="mb-3 text-lg font-semibold text-zinc-900 dark:text-zinc-50">{{ __('ui.profiles') }}</h2>
            @if($profiles->isEmpty())
                <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('ui.notfound') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-zinc-500">
                            <tr>
                                <th class="px-2 py-1">ID</th>
                                <th class="px-2 py-1">User</th>
                                <th class="px-2 py-1">Type</th>
                                <th class="px-2 py-1">Created</th>
                            </tr>
                        </thead>
                        <tbody class="text-zinc-900 dark:text-zinc-100">
                            @foreach($profiles as $profile)
                                <tr class="border-t border-zinc-200 dark:border-zinc-700">
                                    <td class="px-2 py-1">{{ $profile['id'] }}</td>
                                    <td class="px-2 py-1">
                                        {{ $u?->name ?? '—' }} ({{ $u?->email ?? '—' }})
                                    </td>
                                    <td class="px-2 py-1">{{ $profile['type'] }}</td>
                                    <td class="px-2 py-1">{{ $profile['created_at'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-layouts.second_level_layout>