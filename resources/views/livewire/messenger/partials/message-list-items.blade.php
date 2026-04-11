@foreach ($items as $msg)
    @php
        $uid = $msg['user_id'] ?? null;
        $isMine = $uid !== null && (int) $uid === (int) auth()->id();
        $isSystem = ($msg['kind'] ?? '') === 'system';
        $isAssistant = $isAiChat && $uid === null && ! $isSystem;
    @endphp
    <div class="mb-[2px] flex items-start" style="gap: 20px;">
        <div class="mt-1 w-[60px] shrink-0 whitespace-nowrap text-right text-xs opacity-70">
            {{ \Illuminate\Support\Carbon::parse($msg['created_at'])->timezone(config('app.timezone'))->format('d.m H:i') }}
        </div>
        <div class="flex max-w-[70%] flex-col items-start">
            <div
                @class([
                    'mb-1 w-full text-left text-xs font-medium',
                    'text-blue-500 dark:text-blue-400' => $isMine && ! $isSystem,
                    'text-zinc-600 dark:text-zinc-400' => ! $isMine || $isSystem,
                ])
            >
                @if ($isSystem)
                    {{ __('ui.messenger.system') }}
                @elseif ($isAssistant)
                    {{ __('ui.messenger.assistant') }}
                @elseif ($isMine)
                    {{ __('ui.messenger.you_label') }}
                @else
                    {{ $msg['author']['name'] ?? __('ui.messenger.chat') }}
                @endif
            </div>
            <div
                @class([
                    'rounded-lg p-2 text-sm break-words',
                    'ms-4 bg-blue-500 text-white' => $isMine && ! $isSystem,
                    'bg-zinc-200 text-zinc-900 dark:bg-zinc-700 dark:text-zinc-100' => ! $isMine || $isSystem,
                ])
            >
                @if (! empty($msg['is_forward']))
                    <div class="mb-1 text-xs opacity-90">{{ __('ui.messenger.forwarded') }}</div>
                @endif
                <div class="whitespace-pre-wrap">{{ $msg['body'] }}</div>
                @if (! empty($msg['attachments']))
                    <ul class="mt-2 list-inside list-disc text-xs opacity-90">
                        @foreach ($msg['attachments'] as $att)
                            <li>
                                @if (! empty($att['download_url']))
                                    <a href="{{ $att['download_url'] }}" class="underline" target="_blank" rel="noopener noreferrer">{{ $att['original_name'] ?? $att['path'] }}</a>
                                @else
                                    {{ $att['original_name'] ?? $att['path'] }}
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
@endforeach
