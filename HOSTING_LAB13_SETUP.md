# ЛР №13: кеширование на InfinityFree без SSH

Для ветки `hosting-laravel13` используется MySQL и очередь `sync`. В обычном бесплатном InfinityFree нельзя рассчитывать на запуск Artisan через SSH.

1. Сохраните резервную копию рабочей MySQL и файла `.env`. Не загружайте пароль в GitHub.
2. Скачайте последнюю готовую сборку ветки `hosting-laravel13` из GitHub Actions: она включает `vendor` и скомпилированный JS. Замените код на хостинге, но **не затирайте текущие `.env` и данные MySQL**.
3. В существующий `.env` внесите:
   `CACHE_DRIVER=database`
   `CACHE_STORE=database`
   `QUEUE_CONNECTION=sync`
   Сохраните прежние `DB_CONNECTION=mysql`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
4. В phpMyAdmin из панели InfinityFree выберите **действующую базу сайта** и импортируйте `deploy/infinityfree_lab13_cache.sql`. SQL создаст `cache` и `cache_locks`, отметит миграцию в таблице `migrations`, не удаляя новости и пользователей. Если вместо этого доступна команда `php artisan migrate --force` в совместимой среде, используйте миграции **вместо** ручного SQL.
5. Если случайно сохранён старый `bootstrap/cache/config.php`, удалите только этот скомпилированный файл конфигурации. Новый ZIP не содержит его.
6. Проверьте страницы `/news`, `/news?page=2` и `/news/{id}`. Создайте статью, обновите её и одобрите комментарий: сведения должны сразу обновляться.

**Важно:** тесты GitHub Actions проверяют Laravel на изолированной SQLite. Проверка настоящего MySQL и работающего InfinityFree возможна только после фактического развёртывания.
