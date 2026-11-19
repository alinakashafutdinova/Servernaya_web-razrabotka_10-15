<?php
/**
 * One-time, idempotent local setup for Laravel 13 + SQLite (no external hosting).
 * Does not need Composer itself: the launcher installs vendor/ when available.
 */
declare(strict_types=1);

$root = dirname(__DIR__);
chdir($root);

function stopLocal(string $message): void
{
    fwrite(STDERR, "\nОШИБКА: ".$message."\n");
    exit(1);
}

if (version_compare(PHP_VERSION, '8.3.0', '<')) {
    stopLocal('Нужен PHP 8.3.0 или новее. Текущая версия: '.PHP_VERSION);
}
foreach (['pdo_sqlite', 'mbstring', 'openssl', 'fileinfo'] as $extension) {
    if (!extension_loaded($extension)) {
        stopLocal('Не загружено расширение PHP '.$extension.'. Включите его в php.ini и повторите запуск.');
    }
}
if (!file_exists($root.'/vendor/autoload.php')) {
    stopLocal('Нет папки vendor. Скачайте готовый архив из GitHub Actions или выполните composer install.');
}

function localEnvValue(string $contents, string $name): ?string
{
    if (!preg_match('/^'.preg_quote($name, '/').'=(.*)$/m', $contents, $matches)) {
        return null;
    }
    return trim(trim($matches[1]), " \t\n\r\0\x0B\"'");
}

function localSetEnv(string $contents, string $name, string $value): string
{
    $line = $name.'="'.addcslashes($value, "\\\"").'"';
    $pattern = '/^'.preg_quote($name, '/').'=.*$/m';
    if (preg_match($pattern, $contents)) {
        return preg_replace_callback($pattern, static fn () => $line, $contents, 1);
    }
    return rtrim($contents)."\n".$line."\n";
}

$envFile = $root.'/.env';
$isNew = !is_file($envFile);
$template = @file_get_contents($root.'/.env.example');
if ($template === false) {
    stopLocal('Отсутствует файл .env.example.');
}
$env = $isNew ? $template : @file_get_contents($envFile);
if ($env === false) {
    stopLocal('Не удалось прочитать .env.');
}
if ($isNew) {
    $env = localSetEnv($env, 'DB_CONNECTION', 'sqlite');
    $env = localSetEnv($env, 'APP_ENV', 'local');
    $env = localSetEnv($env, 'APP_URL', 'http://127.0.0.1:8000');
    $env = localSetEnv($env, 'MAIL_MAILER', 'log');
}
 // ЛР №13: обновляем кеш и для существующего .env после №10–12.
$env = localSetEnv($env, 'CACHE_DRIVER', 'database');
$env = localSetEnv($env, 'CACHE_STORE', 'database');
if (localEnvValue($env, 'QUEUE_CONNECTION') !== 'database') {
    stopLocal('В локальной версии ЛР №10 требуется QUEUE_CONNECTION=database.');
}
if (localEnvValue($env, 'DB_CONNECTION') !== 'sqlite') {
    stopLocal('Указана не SQLite! Для безопасного локального запуска нужен DB_CONNECTION=sqlite в .env.');
}
if (!localEnvValue($env, 'APP_KEY')) {
    $env = localSetEnv($env, 'APP_KEY', 'base64:'.base64_encode(random_bytes(32)));
}
$moderatorEmail = localEnvValue($env, 'MODERATOR_EMAIL') ?: 'moderator@example.test';
if (!localEnvValue($env, 'MODERATOR_EMAIL')) {
    $env = localSetEnv($env, 'MODERATOR_EMAIL', $moderatorEmail);
}
$password = localEnvValue($env, 'MODERATOR_PASSWORD');
if (!$password) {
    $password = bin2hex(random_bytes(14));
    $env = localSetEnv($env, 'MODERATOR_PASSWORD', $password);
}
if (@file_put_contents($envFile, $env) === false) {
    stopLocal('Не удалось сохранить .env. Проверьте права доступа.');
}
$dbPath = $root.'/database/database.sqlite';
if (!is_file($dbPath) && @file_put_contents($dbPath, '') === false) {
    stopLocal('Не удалось создать database/database.sqlite.');
}

$loginFile = $root.'/LOCAL_LOGIN.txt';
if (!file_exists($loginFile)) {
    $help = "ЛОКАЛЬНЫЙ ДЕМО-АККАУНТ (не для интернета!)\n"
        ."Почта: ".$moderatorEmail."\n"
        ."Пароль: ".$password."\n"
        ."Сайт: http://127.0.0.1:8000\n"
        ."Почтовые письма: storage/logs/laravel.log (MAIL_MAILER=log)\n";
    @file_put_contents($loginFile, $help);
}

function artisanLocal(string $command): void
{
    $root = dirname(__DIR__);
    echo "\n>>> php artisan ".$command."\n";
    $cmd = escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/artisan').' '.$command;
    passthru($cmd, $status);
    if ($status !== 0) {
        stopLocal('Команда artisan '.$command.' завершилась с ошибкой '.$status.'.');
    }
}

artisanLocal('config:clear');
artisanLocal('migrate --force');
artisanLocal('db:seed --force');

echo "\nЛокальная версия подготовлена!\n";
echo "Адрес: http://127.0.0.1:8000\n";
echo "Учётные данные модератора: LOCAL_LOGIN.txt (храните файл только на своём компьютере)\n";
echo "Письма о новых статьях: storage/logs/laravel.log после обработки queue:work\n";
