<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Сбор постов из групп VK</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
        }
        .section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 6px;
        }
        .section h2 {
            color: #555;
            margin-top: 0;
            margin-bottom: 15px;
        }
        .btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: #45a049;
        }
        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .error-message {
            color: #f44336;
            padding: 10px;
            background: #ffebee;
            border-radius: 4px;
            margin-top: 10px;
        }
        .success-message {
            color: #4CAF50;
            padding: 10px;
            background: #f1f8f4;
            border-radius: 4px;
            margin-top: 10px;
        }
        .alert-info {
            color: #0c5460;
            padding: 12px;
            background: #d1ecf1;
            border-radius: 4px;
            margin-bottom: 16px;
        }
        ul { list-style: none; padding: 0; margin: 0; }
        li { padding: 10px 0; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 12px; }
        li:last-child { border-bottom: none; }
        input[type="checkbox"] { width: 18px; height: 18px; }
        label { flex: 1; cursor: pointer; }
        .count { color: #666; font-size: 14px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; margin-bottom: 6px; color: #555; }
        .form-group input[type="number"] { width: 80px; padding: 8px; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 4px; font-size: 14px; }
        pre { background: #fff; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 13px; margin: 6px 0; border: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="container">
        @include('vk_menu')
        <h1>Сбор постов из групп VK</h1>

        @if(session('success'))
            <div class="success-message">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="error-message">{{ session('error') }}</div>
        @endif

        @if(!$hasVkToken)
            <div class="alert-info">
                Сначала получите VK-токен: <a href="{{ route('admin.vk') }}">страница «Токен» (/admin/vk)</a> → кнопка «Войти через OAuth». Токен сохранится в настройках (таблица vk_settings).
            </div>
        @endif

        <div class="section">
            <h2>Запуск сбора постов</h2>
            <form action="{{ route('admin.vk-posts.fetch') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label>Группы для сбора постов (отмеченные уже есть в VkTracking):</label>
                    <ul>
                        @forelse($trackings as $t)
                            <li>
                                <input type="checkbox" name="vk_tracking_ids[]" value="{{ $t->id }}" id="t{{ $t->id }}">
                                <label for="t{{ $t->id }}">{{ $t->name }} ({{ $t->screen_name }})</label>
                                <span class="count">сохранено постов: {{ $postsCount[$t->id] ?? 0 }}</span>
                            </li>
                        @empty
                            <li>Нет активных групп. Добавьте группы в VkTracking (админка MoonShine или БД).</li>
                        @endforelse
                    </ul>
                </div>
                <div class="form-group">
                    <label for="count">Сколько постов за один запрос (1–100):</label>
                    <input type="number" name="count" id="count" value="100" min="1" max="100">
                </div>
                <div class="form-group">
                    <label for="offset">Смещение (offset):</label>
                    <input type="number" name="offset" id="offset" value="0" min="0" placeholder="0 — с первого поста">
                    <span style="color: #666; font-size: 14px; margin-left: 8px;">0 — с первого поста, больше — пропустить N постов</span>
                </div>
                <button type="submit" class="btn" @if(!$hasVkToken || $trackings->isEmpty()) disabled @endif>
                    Запустить сбор постов
                </button>
            </form>
            <p style="margin-top: 15px; color: #666; font-size: 14px;">
                Задачи выполняются воркером очередей. После нажатия кнопки запустите на сервере:
                <code>php artisan queue:work --queue=vk,default</code>
                (или настройте Supervisor — см. <strong>docs/QUEUE.md</strong> в репозитории).
            </p>
        </div>

        <div class="section">
            <h2>Что происходит при запросе к VK API</h2>
            <p style="color: #666; font-size: 14px; margin-bottom: 12px;">
                Выберите группу и нажмите «Показать запрос и ответ» — будет выполнен тот же вызов <code>wall.get</code>, что и в джобе, без постановки в очередь. Увидите URL запроса, параметры и ответ VK.
            </p>
            <form action="{{ route('admin.vk-posts.debug') }}" method="POST" style="margin-bottom: 20px;">
                @csrf
                <div class="form-group">
                    <label>Группа для проверки (выберите одну):</label>
                    <ul>
                        @foreach($trackings as $t)
                            <li>
                                <input type="radio" name="vk_tracking_ids[]" value="{{ $t->id }}" id="debug_t{{ $t->id }}" @if($loop->first) checked @endif>
                                <label for="debug_t{{ $t->id }}">{{ $t->name }} ({{ $t->screen_name }})</label>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="form-group">
                    <label for="debug_count">Сколько постов запросить (1–100):</label>
                    <input type="number" name="count" id="debug_count" value="5" min="1" max="100">
                </div>
                <button type="submit" class="btn" @if(!$hasVkToken || $trackings->isEmpty()) disabled @endif>
                    Показать запрос и ответ
                </button>
            </form>

        @if(!empty($debugLog))
            <div class="section" style="margin-top: 16px;">
                <h3 style="color: #555; margin-top: 0; margin-bottom: 12px;">Результат запроса к VK API</h3>
                @foreach($debugLog as $entry)
                    <div style="margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid #dee2e6;">
                        <strong>{{ $entry['tracking_name'] ?? 'Группа' }}</strong>
                        @if(isset($entry['group_id']))
                            <span style="color: #666;"> (group_id: {{ $entry['group_id'] }}, owner_id в запросе: {{ $entry['owner_id'] ?? '—' }})</span>
                        @endif
                        @if(!empty($entry['error']))
                            <div class="error-message" style="margin-top: 8px;">{{ $entry['error'] }}</div>
                        @else
                            <div style="margin-top: 12px;">
                                <strong>Куда отправляется запрос:</strong>
                                <pre>{{ $entry['request_url'] ?? '—' }}</pre>
                            </div>
                            <div style="margin-top: 8px;">
                                <strong>Параметры запроса:</strong>
                                <pre>@json($entry['request_params'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)</pre>
                            </div>
                            <div style="margin-top: 8px;">
                                <strong>HTTP-статус ответа:</strong> <code>{{ $entry['response_status'] ?? '—' }}</code>
                                @if(isset($entry['response_items_count']))
                                    <span style="margin-left: 12px;">Постов в ответе: <strong>{{ $entry['response_items_count'] }}</strong></span>
                                @endif
                            </div>
                            @if(!empty($entry['response_error']))
                                <div class="error-message" style="margin-top: 8px;">
                                    Ошибка VK API: {{ $entry['response_error']['error_msg'] ?? json_encode($entry['response_error']) }}
                                    (код: {{ $entry['response_error']['error_code'] ?? '—' }})
                                </div>
                            @endif
                            <div style="margin-top: 8px;">
                                <strong>Тело ответа (JSON):</strong>
                                <pre style="max-height: 400px; overflow-y: auto;">@if(is_array($entry['response_body'] ?? null))@json($entry['response_body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)@else{{ $entry['response_body'] ?? '—' }}@endif</pre>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
        </div>
    </div>
</body>
</html>
