<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Сбор постов из групп VK</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; border-radius: 8px; padding: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 20px; }
        .alert { padding: 12px; border-radius: 6px; margin-bottom: 16px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        .alert-info { background: #d1ecf1; color: #0c5460; }
        ul { list-style: none; padding: 0; margin: 0; }
        li { padding: 10px 0; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 12px; }
        li:last-child { border-bottom: none; }
        input[type="checkbox"] { width: 18px; height: 18px; }
        label { flex: 1; cursor: pointer; }
        .count { color: #666; font-size: 14px; }
        .btn { display: inline-block; padding: 12px 24px; background: #4CAF50; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; text-decoration: none; }
        .btn:hover { background: #45a049; }
        .btn:disabled { background: #ccc; cursor: not-allowed; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; margin-bottom: 6px; color: #555; }
        .form-group input[type="number"] { width: 80px; padding: 8px; }
        .mt { margin-top: 20px; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 4px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Сбор постов из групп VK</h1>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        @if(!$hasVkToken)
            <div class="alert alert-info">
                Сначала получите VK-токен: <a href="{{ route('admin.vktest') }}">страница «VK Open API тест»</a> → кнопка «Войти через OAuth». После входа токен сохранится в вашем профиле.
            </div>
        @endif

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
            <button type="submit" class="btn" @if(!$hasVkToken || $trackings->isEmpty()) disabled @endif>
                Запустить сбор постов
            </button>
        </form>

        <div class="mt">
            <p style="color: #666; font-size: 14px;">
                Задачи выполняются воркером очередей. После нажатия кнопки запустите на сервере:
                <code>php artisan queue:work --queue=vk,default</code>
                (или настройте Supervisor — см. <strong>docs/QUEUE.md</strong> в репозитории).
            </p>
        </div>
    </div>
</body>
</html>
