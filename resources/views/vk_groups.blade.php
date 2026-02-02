<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Группы пользователя VK</title>
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
        .group-row {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        .group-row:last-child { border-bottom: none; }
        .group-info {
            flex: 1;
        }
        .group-name {
            font-weight: 600;
            color: #333;
        }
        .group-name a {
            color: #1976d2;
            text-decoration: none;
        }
        .group-name a:hover { text-decoration: underline; }
        .group-screen {
            font-size: 14px;
            color: #666;
        }
        .group-actions {
            flex-shrink: 0;
        }
        .btn-add {
            display: inline-block;
            padding: 8px 16px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-add:hover {
            background: #45a049;
        }
        .btn-add:disabled, .tracked-badge {
            background: #9e9e9e;
            cursor: default;
        }
        .tracked-badge {
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 14px;
            color: white;
        }
        .error-message {
            color: #f44336;
            padding: 10px;
            background: #ffebee;
            border-radius: 4px;
            margin-bottom: 16px;
        }
        .success-message {
            color: #4CAF50;
            padding: 10px;
            background: #f1f8f4;
            border-radius: 4px;
            margin-bottom: 16px;
        }
        .info-message {
            color: #0c5460;
            padding: 10px;
            background: #d1ecf1;
            border-radius: 4px;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        @include('vk_menu')
        <h1>Группы пользователя</h1>

        @if(session('success'))
            <div class="success-message">{{ session('success') }}</div>
        @endif
        @if(session('info'))
            <div class="info-message">{{ session('info') }}</div>
        @endif
        @if(session('error'))
            <div class="error-message">{{ session('error') }}</div>
        @endif
        @if(isset($error) && $error)
            <div class="error-message">{{ $error }}</div>
        @endif

        <div class="section">
            <h2>Список групп</h2>
            @if(empty($groups))
                <p style="color: #666;">Групп нет или не удалось загрузить. Проверьте токен на странице «VK» (нужен доступ groups).</p>
            @else
                <p style="color: #666; margin-bottom: 16px;">Всего групп: {{ count($groups) }}</p>
                @foreach($groups as $group)
                    @php
                        $groupId = (int) ($group['id'] ?? 0);
                        $name = $group['name'] ?? '—';
                        $screenName = $group['screen_name'] ?? '';
                        $isTracked = in_array($groupId, $trackedIds ?? [], true);
                        $groupUrl = $screenName ? 'https://vk.com/' . $screenName : 'https://vk.com/club' . $groupId;
                    @endphp
                    <div class="group-row">
                        <div class="group-info">
                            <div class="group-name">
                                <a href="{{ $groupUrl }}" target="_blank" rel="noopener">{{ $name }}</a>
                            </div>
                            <div class="group-screen">{{ $screenName ? '@' . $screenName : 'club' . $groupId }} · ID: {{ $groupId }}</div>
                        </div>
                        <div class="group-actions">
                            @if($isTracked)
                                <span class="tracked-badge">В отслеживаемых</span>
                            @else
                                <form action="{{ route('admin.vk-groups.add-tracking') }}" method="POST" style="display: inline;">
                                    @csrf
                                    <input type="hidden" name="group_id" value="{{ $groupId }}">
                                    <input type="hidden" name="name" value="{{ $name }}">
                                    <input type="hidden" name="screen_name" value="{{ $screenName }}">
                                    <button type="submit" class="btn-add">Добавить в отслеживаемые</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</body>
</html>
