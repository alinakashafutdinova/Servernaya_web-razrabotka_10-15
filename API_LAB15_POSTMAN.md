# Лабораторная работа №15 — REST API

В Laravel 13 подключен routes/api.php через bootstrap/app.php. Контроллеры app/Http/Controllers/API/AuthController.php и ArticleController.php отвечают JSON вместо Blade и redirect. Старые web-маршруты сохранены, чтобы продолжали работать лабораторные №1–14.

## Проверка через Postman или Insomnia

1. Включите локальный сайт (main/SQLite) либо откройте готовый сайт на InfinityFree. Для локальной версии базовый адрес http://127.0.0.1:8000.
2. Postman → Import → File → docs/postman/LR15_REST_API.postman_collection.json. Insomnia также умеет импортировать Postman Collection v2.1.
3. В Variables поставьте base_url, moderator_email и moderator_password. Последние два значения берутся из вашего приватного LOCAL_LOGIN.txt. Не выкладывайте пароль или токены в GitHub.
4. Проверьте публичный GET /api/articles. Для аутентификации выполните POST /api/register (роль reader) или POST /api/login (модератор). Токен автоматически запишется в переменную token.
5. С токеном модератора последовательно выполните GET /api/articles-management, POST /api/articles, PATCH /api/articles/{id}, PUT /api/articles/{id}, DELETE /api/articles/{id}. POST автоматически сохраняет article_id.
6. Закончите POST /api/logout, затем проверьте, что старый Bearer-токен отклоняется (401).

## Маршруты

| Метод | URL | Доступ | Код успеха |
|---|---|---|---|
| GET | /api/articles?page=1 | Все | 200 |
| GET | /api/articles/{id} | Все (опубликованные) | 200 |
| POST | /api/register | Все | 201 |
| POST | /api/login | Все | 200 |
| GET | /api/user | Sanctum Bearer | 200 |
| POST | /api/logout | Sanctum Bearer | 200 |
| GET | /api/articles-management | Модератор | 200 |
| POST | /api/articles | Модератор | 201 |
| PUT / PATCH | /api/articles/{id} | Модератор | 200 |
| DELETE | /api/articles/{id} | Модератор | 204 |

Для запроса отправляется Accept: application/json. Для JSON-тела также Content-Type: application/json. Защищённые запросы передают заголовок Authorization: Bearer TOKEN. Ошибки: 401 — не авторизован, 403 — нет прав, 404 — не найдено, 422 — ошибки данных.

## Интеграция предыдущих работ

Создание новости через API вызывает VeryLongJob (№10), NewArticleEvent (№11), Notification читателям (№12) и обновление кеша (№13). API-просмотр статьи учитывает middleware TrackArticleView (№14).

## Проверки

Файл tests/Feature/RestApiTest.php содержит интеграционные тесты токенов, CRUD, ролей, опубликованных статей, комментариев и прежнего веб-интерфейса. GitHub Actions проверяет обе ветки с SQLite; настоящие MySQL, SMTP и Pusher требуют отдельной проверки на InfinityFree.

В коллекции находятся учебные значения. Не делайте скриншоты настоящих токенов, паролей и личного .env.