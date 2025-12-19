<x-layouts.second_level_layout :title="__('ui.profiles')" :buttons="$buttons">
        <div class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            @if($data->isEmpty())
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
                            @foreach($data as $profile)
                                <tr class="border-t border-zinc-200 dark:border-zinc-700">
                                    <td class="px-2 py-1">{{ $profile['id'] }}</td>
                                    <td class="px-2 py-1">{{ $profile['user_name'] }}</td>
                                    <td class="px-2 py-1">{{ __('ui.types.values.'.$profile['type_name']) }}</td>
                                    <td class="px-2 py-1">{{ $profile['created_at'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
</x-layouts.second_level_layout>