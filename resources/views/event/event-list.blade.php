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
            
            @if(empty($events))
                <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('ui.notfound') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-zinc-500">
                            <tr>
                                <th class="px-2 py-1">ID</th>
                                <th class="px-2 py-1">{{ __('ui.name') ?? 'Name' }}</th>
                                <th class="px-2 py-1">{{ __('ui.description') ?? 'Description' }}</th>
                                <th class="px-2 py-1">{{ __('moonshine.events.status') }}</th>
                                <th class="px-2 py-1">{{ __('moonshine.events.booked_resource') }}</th>
                                <th class="px-2 py-1">{{ __('moonshine.events.room') }}</th>
                                <th class="px-2 py-1">{{ __('moonshine.events.user') }}</th>
                                <th class="px-2 py-1">{{ __('moonshine.events.start_at') }}</th>
                                <th class="px-2 py-1">{{ __('moonshine.events.end_at') }}</th>
                                <th class="px-2 py-1">{{ __('moonshine.events.price') }}</th>
                                <th class="px-2 py-1">{{ __('moonshine.events.active') }}</th>
                            </tr>
                        </thead>
                        <tbody class="text-zinc-900 dark:text-zinc-100">
                            @foreach($events as $event)
                                <tr class="border-t border-zinc-200 dark:border-zinc-700">
                                    <td class="px-2 py-1">{{ $event['id'] }}</td>
                                    <td class="px-2 py-1">{{ $event['name'] }}</td>
                                    <td class="px-2 py-1">{{ $event['description'] ?? '-' }}</td>
                                    <td class="px-2 py-1">
                                        <span class="px-2 py-1 rounded text-xs
                                            @if($event['status'] === 'confirmed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                            @elseif($event['status'] === 'cancelled') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                            @elseif($event['status'] === 'completed') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                            @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                            @endif">
                                            {{ __('moonshine.events.statuses.' . ($event['status'] ?? 'pending')) }}
                                        </span>
                                    </td>
                                    <td class="px-2 py-1">{{ $event['booked_resource'] ?? '-' }}</td>
                                    <td class="px-2 py-1">{{ $event['room'] ?? '-' }}</td>
                                    <td class="px-2 py-1">{{ $event['user'] ?? '-' }}</td>
                                    <td class="px-2 py-1">{{ $event['start_at'] ?? '-' }}</td>
                                    <td class="px-2 py-1">{{ $event['end_at'] ?? '-' }}</td>
                                    <td class="px-2 py-1">{{ $event['price'] ? number_format($event['price'], 2) . ' ₽' : '-' }}</td>
                                    <td class="px-2 py-1">
                                        <input type="checkbox" 
                                               class="w-4 h-4 text-indigo-600 bg-gray-100 border-gray-300 rounded focus:ring-indigo-500 focus:ring-2" 
                                               {{ $event['active'] ? 'checked' : '' }} 
                                               disabled>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>