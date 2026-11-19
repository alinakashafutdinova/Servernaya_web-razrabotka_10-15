<?php

use App\Http\Controllers\API\ArticleController;
use App\Http\Controllers\API\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ЛР №15 · REST API
|--------------------------------------------------------------------------
| Laravel 13 автоматически применяет префикс /api и stateless middleware.
| Web-маршруты и Blade-страницы лабораторных №1–14 остаются в routes/web.php.
| Токены Laravel Sanctum передаются в заголовке Authorization: Bearer TOKEN.
*/

Route::post('/register', [AuthController::class, 'register'])
    ->middleware('throttle:5,1')->name('api.register');
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:10,1')->name('api.login');

// Доступ к опубликованным новостям без входа.
Route::get('/articles', [ArticleController::class, 'index'])->name('api.articles.index');
Route::get('/articles/{article}', [ArticleController::class, 'show'])
    ->whereNumber('article')->middleware('article.view')->name('api.articles.show');

// Личный кабинет и управление статьями доступны по токену.
Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/user', [AuthController::class, 'me'])->name('api.user');
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');

    Route::get('/articles-management', [ArticleController::class, 'manage'])
        ->name('api.articles.manage');

    Route::post('/articles', [ArticleController::class, 'store'])->name('api.articles.store');
    Route::put('/articles/{article}', [ArticleController::class, 'update'])
        ->whereNumber('article')->name('api.articles.update');
    Route::patch('/articles/{article}', [ArticleController::class, 'update'])
        ->whereNumber('article')->name('api.articles.patch');
    Route::delete('/articles/{article}', [ArticleController::class, 'destroy'])
        ->whereNumber('article')->name('api.articles.destroy');
});
