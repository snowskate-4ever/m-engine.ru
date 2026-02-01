<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Лента пользователя VK</title>
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
        .post {
            background: white;
            border: 1px solid #eee;
            border-radius: 6px;
            padding: 16px;
            margin-bottom: 16px;
        }
        .post:last-child { margin-bottom: 0; }
        .post-meta {
            color: #666;
            font-size: 14px;
            margin-bottom: 8px;
        }
        .post-meta a {
            color: #1976d2;
            text-decoration: none;
        }
        .post-meta a:hover { text-decoration: underline; }
        .post-text {
            white-space: pre-wrap;
            word-break: break-word;
            margin-bottom: 8px;
        }
        .post-attachments {
            font-size: 13px;
            color: #888;
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
        .pagination {
            margin-top: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .pagination a, .pagination span {
            padding: 8px 12px;
            text-decoration: none;
            color: #1976d2;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
        }
        .pagination a:hover { background: #f5f5f5; }
    </style>
</head>
<body>
    <div class="container">
        @include('vk_menu')
        <h1>Лента пользователя</h1>

        @if(session('success'))
            <div class="success-message">{{ session('success') }}</div>
        @endif
        @if(isset($error) && $error)
            <div class="error-message">{{ $error }}</div>
        @elseif(session('error'))
            <div class="error-message">{{ session('error') }}</div>
        @endif

        <div class="section">
            <h2>Посты со стены</h2>
            @if(empty($posts))
                <p style="color: #666;">Постов нет или не удалось загрузить. Проверьте токен на странице «VK».</p>
            @else
                @foreach($posts as $post)
                    @php
                        $ownerId = $post['owner_id'] ?? $vkUserId ?? 0;
                        $postId = $post['id'] ?? 0;
                        $postUrl = 'https://vk.com/wall' . $ownerId . '_' . $postId;
                        $date = isset($post['date']) ? date('d.m.Y H:i', (int) $post['date']) : '—';
                        $text = $post['text'] ?? '';
                        $attachments = $post['attachments'] ?? [];
                        $attTypes = array_map(fn($a) => $a['type'] ?? '', $attachments);
                    @endphp
                    <div class="post">
                        <div class="post-meta">
                            {{ $date }}
                            <a href="{{ $postUrl }}" target="_blank" rel="noopener">Открыть в VK</a>
                        </div>
                        @if($text !== '')
                            <div class="post-text">{{ $text }}</div>
                        @endif
                        @if(!empty($attTypes))
                            <div class="post-attachments">Вложения: {{ implode(', ', $attTypes) }}</div>
                        @endif
                    </div>
                @endforeach
                @if($nextFrom)
                    <div class="pagination">
                        <a href="{{ route('admin.vk-feed.index', ['start_from' => $nextFrom]) }}">Ещё посты</a>
                    </div>
                @endif
            @endif
        </div>
    </div>
</body>
</html>
