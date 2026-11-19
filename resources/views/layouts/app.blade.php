<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#fffafc">
    <meta name="description" content="Курсовой проект · №10–15 по серверной веб-разработке: статьи, авторизация, комментарии и модерация.">
    <title>@yield('title') — Алина Кашафутдинова</title>
    <link rel="stylesheet" href="{{ asset('css/site.css') }}?v=20261119b">
</head>
<body>
    <div class="site-shell">
        <header class="site-header">
            <div class="container header-inner header-inner-refined">
                <a class="brand" href="{{ route('home') }}" aria-label="На главную страницу">
                    <span class="brand-symbol" aria-hidden="true">a<span>.</span></span>
                    <span class="brand-caption">alina <span>•</span> web lab</span>
                </a>
                <div class="header-account-zone">
                    @auth
                        <a class="user-chip" href="{{ route('account') }}" title="Открыть мой профиль" aria-label="Мой профиль: {{ auth()->user()->name }}">
                            <span class="user-chip-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                            <span class="user-chip-copy"><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->isModerator() ? 'Модератор' : 'Читатель' }} · Вы вошли в аккаунт</small></span>
                        </a>
                        <form method="post" action="{{ route('logout') }}" class="header-logout-form">
                            @csrf
                            <button type="submit" class="header-logout">Выйти <span aria-hidden="true">↗</span></button>
                        </form>
                    @else
                        <span class="header-note">КУРСОВОЙ ПРОЕКТ №10–15</span>
                        <a class="header-login" href="{{ route('login') }}">Войти</a>
                        <a class="header-register" href="{{ route('register') }}">Регистрация</a>
                    @endauth
                </div>
            </div>
            <div class="header-nav-row">
                <nav class="navigation container navigation-refined" aria-label="Основное меню">
                    <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home', 'galery') ? 'active' : '' }}" {{ request()->routeIs('home') ? 'aria-current=page' : '' }}>Главная</a>
                    <a href="{{ route('about') }}" class="nav-link {{ request()->routeIs('about') ? 'active' : '' }}" {{ request()->routeIs('about') ? 'aria-current=page' : '' }}>О нас</a>
                    <a href="{{ route('contacts') }}" class="nav-link {{ request()->routeIs('contacts') ? 'active' : '' }}" {{ request()->routeIs('contacts') ? 'aria-current=page' : '' }}>Контакты</a>
                    <a href="{{ route('news.index') }}" class="nav-link {{ request()->routeIs('news.index', 'news.show') ? 'active' : '' }}">Новости</a>
                    @auth
                        @can('create', App\Models\Article::class)
                            <a href="{{ route('news.create') }}" class="nav-link {{ request()->routeIs('news.create') ? 'active' : '' }}">Создать новость</a>
                            <a href="{{ route('news.manage') }}" class="nav-link {{ request()->routeIs('news.manage', 'news.edit') ? 'active' : '' }}">Управление</a>
                        @endcan
                        @can('manage-comments')
                            <a href="{{ route('comments.moderation') }}" class="nav-link {{ request()->routeIs('comments.moderation') ? 'active' : '' }}">Модерация</a>
                        @endcan
                        <a href="{{ route('account') }}" class="nav-link nav-notifications {{ request()->routeIs('account') ? 'active' : '' }}">
                            Мой профиль
                            @if (auth()->user()->unreadNotifications()->count() > 0)
                                <span class="notice-count" aria-label="Непрочитанных уведомлений: {{ auth()->user()->unreadNotifications()->count() }}">{{ auth()->user()->unreadNotifications()->count() }}</span>
                            @endif
                        </a>
                    @endauth
                </nav>
            </div>
        </header>
        <main id="main-content" class="site-main">
            @yield('content')
        </main>
        <footer class="site-footer">
            <div class="container footer-inner">
                <div class="footer-details">
                    <span class="footer-mark">a.</span>
                    <span>Кашафутдинова Алина Тимуровна</span>
                    <span class="footer-separator">/</span>
                    <span>Группа 257-321</span>
                </div>
                <span class="footer-course">Серверная веб-разработка · Курсовой проект · №10–15</span>
            </div>
        </footer>
    </div>
</body>
</html>
