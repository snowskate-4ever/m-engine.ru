<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Новая задача создана</title>
</head>
<body>
    <p>Здравствуйте!</p>
    <p>Для вас создана новая задача.</p>
    <p><strong>Заголовок:</strong> {{ $task->name }}</p>
    <p><strong>Описание:</strong> {{ $task->description ?? '—' }}</p>
    <p><strong>Статус:</strong> {{ $task->status }}</p>
    @if($task->done_at)
        <p><strong>Дата завершения:</strong> {{ $task->done_at->format('Y-m-d H:i') }}</p>
    @endif
    @if($attachmentUrl)
        <p><strong>Вложение:</strong> <a href="{{ $attachmentUrl }}">{{ $attachmentUrl }}</a></p>
    @endif
</body>
</html>

