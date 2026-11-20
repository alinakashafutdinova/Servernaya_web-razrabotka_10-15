<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Статистика сайта</title>
</head>
<body style="margin:0;padding:0;background-color:#fff8fb;color:#312934;font-family:Arial,Helvetica,sans-serif;">
    {{-- Короткий предпросмотр для списка писем, не показывается внутри письма. --}}
    <div style="display:none!important;visibility:hidden;opacity:0;color:transparent;height:0;width:0;overflow:hidden;mso-hide:all;">
        Просмотры: {{ $viewsCount }} · Комментарии: {{ $commentsCount }}. Отчёт за {{ \Carbon\CarbonImmutable::parse($reportDate)->format('d.m.Y') }}.
    </div>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#fff8fb;border-collapse:collapse;">
        <tr><td align="center" style="padding:34px 16px;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:560px;border:1px solid #f2e4ea;background:#ffffff;border-collapse:separate;border-spacing:0;border-radius:18px;">
                <tr><td style="padding:32px 32px 14px;">
                    <p style="font-size:12px;line-height:1.4;font-weight:700;letter-spacing:1px;color:#d34183;margin:0 0 16px;">ВЕБ-ЛАБОРАТОРИЯ</p>
                    <h1 style="font-size:26px;line-height:1.25;color:#322934;margin:0 0 12px;">Статистика сайта</h1>
                    <p style="font-size:15px;color:#746571;line-height:1.5;margin:0;">{{ \Carbon\CarbonImmutable::parse($reportDate)->format('d.m.Y') }} · по московскому времени</p>
                </td></tr>
                <tr><td style="padding:12px 32px 12px;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:separate;border-spacing:0;">
                        <tr>
                            <td width="50%" valign="top" style="padding:20px 12px;background:#fff0f6;border-radius:12px;">
                                <p style="margin:0 0 8px;font-size:12px;line-height:1.4;color:#806373;">ПРОСМОТРЫ СТАТЕЙ</p>
                                <p style="margin:0;color:#c73577;font-size:31px;line-height:1.1;font-weight:700;">{{ $viewsCount }}</p>
                            </td>
                            <td width="8" style="font-size:0;">&nbsp;</td>
                            <td width="50%" valign="top" style="padding:20px 12px;background:#fff0f6;border-radius:12px;">
                                <p style="margin:0 0 8px;font-size:12px;line-height:1.4;color:#806373;">НОВЫЕ КОММЕНТАРИИ</p>
                                <p style="margin:0;color:#c73577;font-size:31px;line-height:1.1;font-weight:700;">{{ $commentsCount }}</p>
                            </td>
                        </tr>
                    </table>
                </td></tr>
                <tr><td style="padding:14px 32px 32px;">
                    <p style="font-size:14px;line-height:1.65;color:#5e515c;margin:0 0 20px;">Сводка за день: учитываются открытия опубликованных статей и сохранённые комментарии.</p>
                    <p style="font-size:12px;line-height:1.55;color:#978893;margin:0;border-top:1px solid #f4e8ee;padding-top:18px;">Автоматический отчёт Laravel · лабораторная №14. Отвечать на письмо не требуется.</p>
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
