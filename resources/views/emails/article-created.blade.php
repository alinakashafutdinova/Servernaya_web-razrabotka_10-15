<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Новая статья</title>
</head>
<body style="margin:0;padding:30px 12px;background:#fff7fa;color:#342c35;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" cellspacing="0" cellpadding="0" style="width:100%;max-width:620px;margin:0 auto;background:#fff;border:1px solid #f2e4eb;border-radius:18px;">
        <tr><td style="padding:30px 34px;">
            <p style="margin:0 0 14px;color:#e7478a;font-size:12px;font-weight:bold;letter-spacing:2px;">ВЕБ-ЛАБОРАТОРИЯ · НОВОСТИ</p>
            <h1 style="margin:0 0 20px;font-size:28px;line-height:1.3;">Добавлена новая статья</h1>
            <p style="margin:0 0 8px;color:#8a7781;font-size:14px;">Заголовок</p>
            <h2 style="margin:0 0 20px;font-size:20px;line-height:1.5;">{{ $article->title }}</h2>
            <p style="margin:0 0 8px;color:#8a7781;font-size:14px;">Дата публикации: {{ $article->published_at->format('d.m.Y') }}</p>
            <p style="margin:18px 0 28px;font-size:15px;line-height:1.7;">{{ $article->summary }}</p>
            <a href="{{ route('news.show', $article) }}" style="display:inline-block;padding:14px 22px;border-radius:10px;background:#fa4d92;color:#fff;text-decoration:none;font-weight:bold;">Открыть статью</a>
            <p style="margin:28px 0 0;font-size:12px;line-height:1.6;color:#8a7781;">Это автоматическое уведомление для модератора. Отвечать на него не требуется.</p>
        </td></tr>
    </table>
</body>
</html>
