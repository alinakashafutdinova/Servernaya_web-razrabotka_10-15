<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Ежедневная статистика</title>
</head>
<body style="margin:0;padding:30px 12px;background:#fff7fa;color:#362a35;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" style="width:100%;max-width:580px;margin:0 auto;background:#fff;border:1px solid #f2e3eb;border-radius:18px;">
        <tr><td style="padding:30px;">
            <p style="font-size:12px;color:#da4c90;letter-spacing:2px;font-weight:bold;">КУРСОВОЙ ПРОЕКТ · СТАТИСТИКА</p>
            <h1 style="font-size:25px;line-height:1.3;margin:14px 0;">Отчёт за {{ $reportDate }}</h1>
            <p style="line-height:1.6;">Здравствуйте! Ниже сведения об использовании сайта за день (время сайта: Москва).</p>
            <div style="border-left:4px solid #f26fa9;background:#fff2f7;padding:15px 18px;margin:22px 0;">
                <p style="margin:0 0 13px;">Просмотры страниц новостей: <strong>{{ $viewsCount }}</strong></p>
                <p style="margin:0;">Новые комментарии: <strong>{{ $commentsCount }}</strong></p>
            </div>
            <p style="color:#74656e;font-size:13px;line-height:1.6;">Считаются успешные открытия страниц опубликованных статей и комментарии, которые сохранены в базе данных за указанную дату.</p>
            <p style="color:#74656e;font-size:12px;margin-top:24px;">Письмо создано планировщиком Laravel автоматически либо по запросу модератора.</p>
        </td></tr>
    </table>
</body>
</html>
