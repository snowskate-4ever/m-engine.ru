<div>
    <div class="card">
        <div class="card-body">
            <!-- Сообщение об успехе -->
            @if (session()->has('success'))
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            
            @auth
            <!-- Поле поиска -->
            <div class="mb-4">
                <label for="search" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    {{ __('ui.search') }}
                </label>
                <div class="relative">
                    <input 
                        type="text" 
                        id="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('ui.search') }}..."
                        class="w-full border rounded-lg block disabled:shadow-none dark:shadow-none appearance-none text-base sm:text-sm py-2 h-10 leading-[1.375rem] ps-3 pe-10 bg-white dark:bg-white/10 dark:disabled:bg-white/[7%] text-zinc-700 disabled:text-zinc-500 placeholder-zinc-400 disabled:placeholder-zinc-400/70 dark:text-zinc-300 dark:disabled:text-zinc-400 dark:placeholder-zinc-400 dark:disabled:text-zinc-500 shadow-xs border-zinc-200 border-b-zinc-300/80 disabled:border-b-zinc-200 dark:border-white/10 dark:disabled:border-white/5 focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 focus:ring-offset-accent-foreground"
                    >
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                        <svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            @endauth
            
            @if(empty($resources))
                <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('ui.notfound') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-zinc-500">
                            <tr>
                                <th class="px-2 py-1">ID</th>
                                <th class="px-2 py-1">{{ __('ui.name') ?? 'Name' }}</th>
                                <th class="px-2 py-1">{{ __('ui.description') ?? 'Description' }}</th>
                                <th class="px-2 py-1">{{ __('moonshine.resources.active') }}</th>
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
                                    <td class="px-2 py-1">
                                        <input type="checkbox" 
                                               class="w-4 h-4 text-indigo-600 bg-gray-100 border-gray-300 rounded focus:ring-indigo-500 focus:ring-2" 
                                               {{ $resource['active'] ? 'checked' : '' }} 
                                               disabled>
                                    </td>
                                    <td class="px-2 py-1">{{ $resource['type_name'] }}</td>
                                    <td class="px-2 py-1">{{ $resource['start_at'] ?: '-' }}</td>
                                    <td class="px-2 py-1">{{ $resource['end_at'] ?: '-' }}</td>
                                    <td class="px-2 py-1">{{ $resource['created_at'] ?: '-' }}</td>
                                    <td class="px-2 py-1">{{ $resource['updated_at'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>