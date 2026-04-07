<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body>
<p><strong>{{ $item->title }}</strong></p>
<p>Напоминание от ассистента m-engine.</p>
@if (! empty($item->payload['event_id']))
<p>Событие #{{ $item->payload['event_id'] }}</p>
@endif
<p><a href="{{ config('app.url') }}">Открыть приложение</a></p>
</body>
</html>
