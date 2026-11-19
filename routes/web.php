<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\MainController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\UserSessionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MainController::class, 'index'])->name('home');
Route::get('/galery/{id}', [MainController::class, 'gallery'])->whereNumber('id')->name('galery');

Route::view('/about', 'about')->name('about');

Route::get('/contacts', function () {
    $contacts = [
        [
            'title' => 'GitHub',
            'value' => '@alinakashafutdinova',
            'description' => 'Мой профиль и другие проекты',
            'url' => 'https://github.com/alinakashafutdinova',
            'icon' => '01',
        ],
        [
            'title' => 'Репозиторий',
            'value' => 'Серверная веб-разработка',
            'description' => 'Исходный код лабораторных работ №1–9',
            'url' => 'https://github.com/alinakashafutdinova/Servernaya_web-razrabotka',
            'icon' => '02',
        ],
    ];

    return view('contacts', ['contacts' => $contacts]);
})->name('contacts');

Route::get('/signin', [AuthController::class, 'create'])->name('signin.create');
Route::post('/signin', [AuthController::class, 'registration'])->name('signin.registration');

Route::middleware('guest')->group(function () {
    Route::get('/register', [UserSessionController::class, 'registerForm'])->name('register');
    Route::post('/register', [UserSessionController::class, 'register'])->name('register.store');
    Route::get('/login', [UserSessionController::class, 'loginForm'])->name('login');
    Route::post('/login', [UserSessionController::class, 'login'])->name('login.store')->middleware('throttle:10,1');
});

Route::post('/logout', [UserSessionController::class, 'logout'])->middleware('auth:sanctum')->name('logout');

Route::get('/news', [NewsController::class, 'index'])->name('news.index');
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/account', [AccountController::class, 'show'])->name('account');
    Route::post('/account/notifications/read', [AccountController::class, 'readNotifications'])->name('account.notifications.read');
    Route::post('/account/notifications/{notification}/open', [AccountController::class, 'openArticleNotification'])
        ->whereUuid('notification')->name('account.notifications.open');
    Route::get('/management', [NewsController::class, 'manage'])->name('news.manage');
    Route::get('/news/create', [NewsController::class, 'create'])->name('news.create');
    Route::post('/news', [NewsController::class, 'store'])->name('news.store');
    Route::get('/news/{article}/edit', [NewsController::class, 'edit'])->whereNumber('article')->name('news.edit');
    Route::put('/news/{article}', [NewsController::class, 'update'])->whereNumber('article')->name('news.update');
    Route::delete('/news/{article}', [NewsController::class, 'destroy'])->whereNumber('article')->name('news.destroy');

    Route::get('/moderation', [CommentController::class, 'moderation'])->name('comments.moderation');
    Route::patch('/comments/{comment}/approve', [CommentController::class, 'approve'])
        ->whereNumber('comment')->name('comments.approve');
    Route::delete('/comments/{comment}/reject', [CommentController::class, 'reject'])
        ->whereNumber('comment')->name('comments.reject');

    Route::post('/news/{article}/comments', [CommentController::class, 'store'])
        ->whereNumber('article')->name('comments.store');
});

Route::get('/news/{article}', [NewsController::class, 'show'])->whereNumber('article')->name('news.show');
