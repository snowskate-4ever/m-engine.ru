<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Лента новостей VK</title>
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
        .item {
            background: white;
            border: 1px solid #eee;
            border-radius: 6px;
            padding: 16px;
            margin-bottom: 16px;
        }
        .item:last-child { margin-bottom: 0; }
        .item-meta {
            color: #666;
            font-size: 14px;
            margin-bottom: 8px;
        }
        .item-meta a {
            color: #1976d2;
            text-decoration: none;
        }
        .item-meta a:hover { text-decoration: underline; }
        .item-text {
            white-space: pre-wrap;
            word-break: break-word;
            margin-bottom: 8px;
        }
        .item-type {
            font-size: 12px;
            color: #888;
        }
        .error-message {
            color: #f44336;
            padding: 10px;
            background: #ffebee;
            border-radius: 4px;
            margin-bottom: 16px;
        }
        .load-more {
            margin-top: 20px;
        }
        .load-more a {
            display: inline-block;
            padding: 10px 20px;
            background: #1976d2;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .load-more a:hover {
            background: #1565c0;
        }
    </style>
</head>
<body>
    <div class="container">
        @include('vk_menu')
        <h1>Лента новостей</h1>

        @if(isset($error) && $error)
            <div class="error-message">{{ $error }}</div>
        @elseif(session('error'))
            <div class="error-message">{{ session('error') }}</div>
        @endif

        <div class="section">
            <h2>Новости</h2>
            @if(empty($items))
                <p style="color: #666;">Записей нет или не удалось загрузить. Метод newsfeed.get может быть недоступен для веб-приложений — проверьте токен на странице «VK».</p>
            @else
                @foreach($items as $item)
                    @php
                        $type = $item['type'] ?? 'post';
                        $sourceId = $item['source_id'] ?? 0;
                        $date = isset($item['date']) ? date('d.m.Y H:i', (int) $item['date']) : '—';
                        $text = $item['text'] ?? '';
                        $postId = $item['post_id'] ?? $item['id'] ?? 0;
                        $link = '';
                        if ($type === 'wall' && $sourceId && $postId) {
                            $link = 'https://vk.com/wall' . $sourceId . '_' . $postId;
                        } elseif (!empty($item['post_id']) && $sourceId) {
                            $link = 'https://vk.com/wall' . $sourceId . '_' . ($item['post_id'] ?? '');
                        }
                    @endphp
                    <div class="item">
                        <div class="item-meta">
                            <span class="item-type">{{ $type }}</span> · {{ $date }}
                            @if($link)
                                <a href="{{ $link }}" target="_blank" rel="noopener">Открыть в VK</a>
                            @endif
                        </div>
                        @if($text !== '')
                            <div class="item-text">{{ $text }}</div>
                        @endif
                    </div>
                @endforeach
                @if($nextFrom)
                    <div class="load-more">
                        <a href="{{ route('admin.vk-newsfeed.index', ['start_from' => $nextFrom]) }}">Загрузить ещё</a>
                    </div>
                @endif
            @endif
        </div>
    </div>
</body>
</html>
