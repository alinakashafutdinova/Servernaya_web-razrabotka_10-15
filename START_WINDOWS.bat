@echo off
setlocal
cd /d "%~dp0"
where php >nul 2>&1
if errorlevel 1 (
  echo PHP 8.3+ was not found in PATH.
  pause
  exit /b 1
)
if not exist vendor\autoload.php (
  where composer >nul 2>&1
  if errorlevel 1 (
    echo Composer missing. Download package with vendor.
    pause
    exit /b 1
  )
  call composer install --no-interaction --prefer-dist --no-progress
  if errorlevel 1 exit /b 1
)
php scripts\setup-local.php
if errorlevel 1 (
  pause
  exit /b 1
)
start "Laravel Queue Worker" cmd /k "php artisan queue:work database --tries=3 --sleep=1"
echo Open http://127.0.0.1:8000
php artisan serve --host=127.0.0.1 --port=8000
pause
