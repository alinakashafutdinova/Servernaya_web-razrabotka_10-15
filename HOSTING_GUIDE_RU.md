> **Бесплатный InfinityFree:** инструкция ниже содержит также рекомендации для хостинга с SSH и настраиваемым Document Root. Для обновления кеша №13 следуйте [HOSTING_LAB13_SETUP.md](HOSTING_LAB13_SETUP.md) и используйте готовый ZIP GitHub Actions. Обе ветки курсовой работают на Laravel 13.

> Для ЛР №10 на InfinityFree используйте `QUEUE_CONNECTION=sync`: класс VeryLongJob выполняется при запросе. Реальная `queue:work database` демонстрируется в локальной ветке `main`.

# Развёртывание курсового проекта №10–15 на InfinityFree

Эта инструкция предназначена для **ветки hosting-laravel13**. Основная ветка `main` сохраняет локальную версию на Laravel 13 и SQLite.

## 1. Требования к хостингу

- PHP **8.3 или 8.4**, Composer 2 и SSH/терминал (либо возможность установить зависимости локально и загрузить папку `vendor`).
- MySQL 8 или совместимая MariaDB, панель phpMyAdmin.
- PHP-расширения: `pdo_mysql`, `mbstring`, `openssl`, `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `iconv`, `intl`, `json`, `libxml`, `pcre`, `session`, `tokenizer`, `xml`, `xmlwriter`, `zip`.
- Возможность настроить **Document Root домена на папку `public`** приложения.
- HTTPS (SSL-сертификат). Должны быть разрешены исходящие SMTP-соединения на порт 465 для настоящих писем.

**Никогда не назначайте Document Root на корень Laravel-проекта и не переносите весь Laravel-проект в публичный `public_html`.**

## 2. Создание MySQL через панель

1. В панели хостинга откройте **Базы данных → MySQL → Создать БД**.
2. Создайте базу и отдельного пользователя для сайта, предоставьте ему права на эту базу.
3. Сохраните значения **имени БД**, **пользователя**, **пароля** и **DB_HOST**; многие хостеры используют имя вида `account_labdb`, а не просто `labdb`.
4. В phpMyAdmin пока ничего импортировать не нужно: **Laravel создаст таблицы миграциями**.

phpMyAdmin — интерфейс управления MySQL; это не отдельная база данных. В нём после установки появятся таблицы `articles`, `users`, `roles`, `comments`, `personal_access_tokens` и `migrations`.

## 3. Загрузка проекта

Рекомендуемый пример структуры на хостинге:

```text
/home/username/
  laravel-site/
    app/
    bootstrap/
    config/
    database/
    public/              ← Document Root сайта
      index.php
      .htaccess
      css/
      images/
    resources/
    routes/
    storage/
    vendor/              ← после установки Composer
    artisan
    composer.json
    .env                  ← только на сервере, вне public
```

Загрузите файлы проекта через Git/SSH или файловый менеджер. Укажите Document Root домена в панели как `/home/username/laravel-site/public`. Если ваш тариф не позволяет выбрать Document Root, смените тариф или хостинг на совместимый с Laravel: **не перемещайте `.env`, `app`, `vendor`, `database` и `storage` в public_html**.

## 4. Настройка .env

В терминале SSH:

```bash
cd /home/username/laravel-site
cp .env.example .env
```

Откройте `.env` в редакторе панели и задайте реальные значения:

```dotenv
APP_NAME="Лабораторные работы №1–9"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://ваш-домен.ru
APP_KEY=
SESSION_DRIVER=file
SESSION_SECURE_COOKIE=true
CACHE_STORE=file
QUEUE_CONNECTION=sync

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=имя_базы_из_панели
DB_USERNAME=пользователь_из_панели
DB_PASSWORD="ваш_пароль_базы"

MODERATOR_EMAIL=ваша_почта@example.ru
MODERATOR_PASSWORD="сложный_пароль_от_12_символов"

MAIL_MAILER=log
MAIL_SCHEME=smtps
MAIL_HOST=smtp.mail.ru
MAIL_PORT=465
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME="Лабораторные работы №1–9"
```

Настройте `DB_HOST` именно как указано у провайдера (вместо `localhost` может быть другой адрес). Значения не нужно заключать в кавычки, если в них нет пробелов или спецсимволов. Пароли `.env` **никому не пересылайте и не загружайте в GitHub**.

## 5. Установка зависимостей, ключа и таблиц

Запустите через SSH из корня `laravel-site`:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=DatabaseSeeder --force
php artisan optimize
```

**Важно:** `migrate` создаёт таблицы. `db:seed` добавляет роли, модератора и 12 демонстрационных новостей с Faker. `migrate:fresh` на живом сайте использовать нельзя — он удаляет таблицы и все данные. Последующие обновления выполняйте через `php artisan migrate --force`, а не `migrate:fresh`.

Пароль модератора берётся из `MODERATOR_PASSWORD`. Для самой первой установки он должен содержать минимум 12 символов. После создания модератора можно очистить пароль из `.env`, но тогда повторный `ModeratorSeeder` не сменит существующий пароль. **Не оставляйте примерные учётные данные в публичном доступе.**

Если при первой установке Composer предупреждает об отсутствии `composer.lock`, сначала на PHP 8.3 выполните `composer update --no-dev` в доверенном окружении для создания lock-файла, затем используйте `composer install --no-dev`. Для воспроизводимых обновлений храните `composer.lock` в приватной копии проекта или репозитории.

## 6. SMTP Mail.ru

Для начальной проверки оставьте `MAIL_MAILER=log` и убедитесь, что после создания статьи в `storage/logs/` появляется запись об отправке. Затем создайте отдельный пароль внешнего приложения в настройках безопасности Mail.ru и заполните:

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=smtps
MAIL_HOST=smtp.mail.ru
MAIL_PORT=465
MAIL_USERNAME=реальный_ящик@mail.ru
MAIL_PASSWORD="пароль_внешнего_приложения"
MAIL_FROM_ADDRESS=реальный_ящик@mail.ru
```

Пароль почты от учётной записи сюда не подходит — используйте пароль приложения. Почтовая рассылка лабораторной №8 отправляется только после успешного создания статьи и только модераторам. После изменения `.env` выполните `php artisan optimize:clear && php artisan optimize`. Некоторые хостинги блокируют исходящий SMTP-порт: в этом случае уточните у поддержки хостинга правила отправки почты.

## 7. Проверка готового сайта

1. Перейдите на `/`: список из оригинального `articles.json` и фотографии доступны.
2. Откройте `/news`: отображаются опубликованные новости с пагинацией.
3. Откройте любую статью `/news/1`: видны полный текст и только одобренные комментарии.
4. Зарегистрируйтесь через `/register`: созданный пользователь получает роль читателя.
5. Войдите модератором через `/login`: доступны управление статьями и `/moderation`.
6. Создайте новую статью: запись появляется в MySQL, письмо формируется для модератора.
7. Добавьте комментарий читателем: появляется сообщение о модерации, комментарий скрыт.
8. Подтвердите комментарий модератором: он станет публичным.
9. Выйдите из учётной записи: защищённые страницы снова требуют входа.

Если сайт возвращает 500, посмотрите последнюю ошибку в `storage/logs/laravel.log`, а также журнал ошибок PHP хостинга. **Никогда не включайте `APP_DEBUG=true` на публичном сайте.**

## 8. Если нет SSH/Composer в панели

Попросите хостинг подключить PHP 8.3+, Composer и указать корневую папку `public`; либо подготовьте `vendor` в среде с совместимой версией PHP и загрузите вместе с проектом. Без `vendor` Laravel не запускается. Простое копирование файлов через FTP без зависимостей не подходит.

## 9. Полезные команды

```bash
php artisan about
php artisan route:list
php artisan migrate:status
php artisan optimize:clear
php artisan optimize
```

### Исходный код

Для передачи преподавателю используйте GitHub-репозиторий с девятью лабораторными работами. Рабочая версия для хостинга — отдельная ветка `hosting-laravel13`; учебный код первой ветки при этом не затрагивается.
