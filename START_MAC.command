#!/bin/bash
set -e
cd "$(dirname "$0")"
if ! command -v php >/dev/null 2>&1; then
  echo "Не найден PHP 8.3+ (с SQLite и mbstring)."; exit 1
fi
PHP_BIN="$(command -v php)"
if [ ! -f vendor/autoload.php ]; then
  if ! command -v composer >/dev/null 2>&1; then
    echo "Нет Composer. Скачайте готовый архив с папкой vendor."; exit 1
  fi
  composer install --no-interaction --prefer-dist --no-progress
fi
"$PHP_BIN" scripts/setup-local.php
"$PHP_BIN" artisan queue:work database --tries=3 --sleep=1 > storage/logs/queue-worker.log 2>&1 &
WORKER=$!
"$PHP_BIN" artisan schedule:work > storage/logs/daily-scheduler.log 2>&1 &
SCHEDULER=$!
trap 'kill "$WORKER" "$SCHEDULER" 2>/dev/null || true' EXIT INT TERM
echo "Адрес: http://127.0.0.1:8000 (очередь и планировщик запущены)"
"$PHP_BIN" artisan serve --host=127.0.0.1 --port=8000
