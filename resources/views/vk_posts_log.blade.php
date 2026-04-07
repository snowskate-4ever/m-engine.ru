<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Лог загрузки постов VK</title>
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
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 4px;
            overflow: hidden;
            border: 1px solid #eee;
        }
        th, td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #f5f5f5;
            color: #555;
            font-weight: 600;
        }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #fafafa; }
        .pagination { margin-top: 20px; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        .pagination a, .pagination span {
            padding: 8px 12px;
            text-decoration: none;
            color: #1976d2;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
        }
        .pagination a:hover { background: #f5f5f5; }
        .pagination .current { background: #1976d2; color: white; border-color: #1976d2; }
        .pagination .disabled { color: #999; cursor: not-allowed; pointer-events: none; }
    </style>
</head>
<body>
    <div class="container">
        @include('vk_menu')
        <h1>Лог загрузки постов</h1>

        <div class="section">
            <h2>Загруженные посты</h2>
            @if($posts->isEmpty())
                <p style="color: #666;">Записей пока нет. Запустите сбор постов на странице «Посты».</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Дата и время записи</th>
                            <th>Название группы</th>
                            <th>Номер поста</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($posts as $post)
                            <tr>
                                <td>{{ $post->created_at->setTimezone('Europe/Moscow')->format('d.m.Y H:i:s') }}</td>
                                <td>{{ $post->vkTracking?->name ?? '—' }} <span style="color:#666;">({{ $post->vkTracking?->screen_name ?? '—' }})</span></td>
                                <td>{{ $post->vk_post_id }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="pagination">
                    {{ $posts->links() }}
                </div>
            @endif
        </div>
    </div>
</body>
</html>
