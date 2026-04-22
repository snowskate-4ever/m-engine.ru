@php
    /** @var \Illuminate\Support\Collection<int,\App\Models\Conversation> $conversations */
@endphp

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Support чат</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-zinc-100 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
    <div class="mx-auto w-full max-w-7xl space-y-4 p-4">
        <h1 class="text-xl font-semibold">Support чат (админка)</h1>

        @if(session('status'))
            <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <section class="rounded border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="mb-3 text-sm font-semibold uppercase text-zinc-500">Диалоги</h2>
                <div class="space-y-2">
                    @forelse($conversations as $conversation)
                        @php
                            $customerId = app(\App\Services\Messenger\SupportChatService::class)->customerId($conversation);
                            $customer = $customerId ? \App\Models\User::query()->find($customerId) : null;
                        @endphp
                        <a
                            href="{{ route('admin.support-chats.index', ['conversation' => $conversation->id]) }}"
                            class="block rounded border px-3 py-2 text-sm {{ $activeConversation?->id === $conversation->id ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-950/40' : 'border-zinc-200 dark:border-zinc-700' }}"
                        >
                            <div class="font-medium">{{ $customer?->name ?? 'Пользователь #'.$customerId }}</div>
                            <div class="text-xs text-zinc-500">{{ $customer?->email }}</div>
                        </a>
                    @empty
                        <div class="text-sm text-zinc-500">Support-диалогов пока нет</div>
                    @endforelse
                </div>
            </section>

            <section class="rounded border border-zinc-200 bg-white p-3 lg:col-span-2 dark:border-zinc-700 dark:bg-zinc-900">
                @if($activeConversation)
                    <div class="mb-3 text-sm text-zinc-500">
                        Клиент: <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $activeCustomer?->name }}</span>
                        ({{ $activeCustomer?->email }})
                    </div>

                    <div class="max-h-[480px] space-y-2 overflow-y-auto rounded border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-950">
                        @forelse($activeMessages as $row)
                            @php
                                $mine = ($row['user_id'] ?? null) === app(\App\Services\Messenger\SupportChatService::class)->resolveSupportUser()?->id;
                            @endphp
                            <div class="rounded px-3 py-2 text-sm {{ $mine ? 'bg-indigo-100 dark:bg-indigo-900/40' : 'bg-white dark:bg-zinc-900' }}">
                                <div class="mb-1 text-xs text-zinc-500">
                                    {{ $row['author']['name'] ?? 'System' }} · {{ $row['created_at'] ?? '' }}
                                </div>
                                <div class="whitespace-pre-wrap">{{ $row['body'] ?? '' }}</div>
                            </div>
                        @empty
                            <div class="text-sm text-zinc-500">Нет сообщений</div>
                        @endforelse
                    </div>

                    @if($aiEnabled)
                        <div class="mt-4 grid grid-cols-1 gap-3 lg:grid-cols-2">
                            <form method="POST" action="{{ route('admin.support-chats.ai-draft', $activeConversation) }}" class="space-y-2">
                                @csrf
                                <button type="submit" class="rounded bg-zinc-800 px-3 py-2 text-sm text-white hover:bg-zinc-700">
                                    Сгенерировать черновик AI
                                </button>
                            </form>
                            @if($allowAutoSend)
                                <form method="POST" action="{{ route('admin.support-chats.ai-send', $activeConversation) }}" class="space-y-2">
                                    @csrf
                                    <button type="submit" class="rounded bg-amber-600 px-3 py-2 text-sm text-white hover:bg-amber-500">
                                        Сгенерировать и отправить AI
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endif

                    @if(is_string($draft) && $draft !== '')
                        <div class="mt-4 rounded border border-indigo-300 bg-indigo-50 p-3 dark:border-indigo-700 dark:bg-indigo-950/40">
                            <div class="mb-2 text-sm font-semibold">AI-черновик</div>
                            <div class="whitespace-pre-wrap text-sm">{{ $draft }}</div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.support-chats.reply', $activeConversation) }}" class="mt-4 space-y-2">
                        @csrf
                        <label class="text-sm font-medium">Ответ клиенту</label>
                        <textarea name="body" rows="5" class="w-full rounded border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" required>{{ old('body', is_string($draft) ? $draft : '') }}</textarea>
                        @error('body')
                            <div class="text-xs text-red-600">{{ $message }}</div>
                        @enderror
                        <button type="submit" class="rounded bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-500">
                            Отправить ответ
                        </button>
                    </form>
                @else
                    <div class="text-sm text-zinc-500">Выберите диалог слева</div>
                @endif
            </section>
        </div>
    </div>
</body>
</html>
