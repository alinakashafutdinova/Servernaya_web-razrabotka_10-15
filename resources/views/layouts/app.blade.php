<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#fffafc">
    <meta name="description" content="Курсовой проект · №10–15 по серверной веб-разработке: статьи, авторизация, комментарии и модерация.">
    <title>@yield('title') — Алина Кашафутдинова</title>
    <link rel="stylesheet" href="{{ asset('css/site.css') }}?v=20261120nav2">
    <link rel="stylesheet" href="{{ asset('css/notifications.css') }}?v=20261120nav2">
    @php
        $broadcastPublic = [
            'enabled' => config('broadcasting.default') === 'pusher',
            'key' => config('broadcasting.connections.pusher.key'),
            'cluster' => config('broadcasting.connections.pusher.options.cluster'),
        ];
    @endphp
    <script>
        // Публикуем только ключ и кластер. Pusher App Secret остаётся на сервере.
        window.__BROADCAST_PUBLIC__ = {{ \Illuminate\Support\Js::from($broadcastPublic) }};
    </script>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
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
                        @php
                            $unreadCount = auth()->user()->unreadNotifications()->count();
                            $unreadItems = auth()->user()->unreadNotifications()->latest()->limit(6)->get();
                        @endphp
                        <details class="readers-notifications">
                            <summary class="readers-notifications__trigger" aria-label="Уведомления. Непрочитанных: {{ $unreadCount }}" title="Уведомления">
                                <span class="readers-notifications__bell" aria-hidden="true">🔔</span>
                                @if ($unreadCount > 0)
                                    <span class="notice-count" aria-hidden="true">{{ $unreadCount }}</span>
                                @endif
                            </summary>
                            <div class="readers-notifications__menu" aria-label="Непрочитанные уведомления">
                                <div class="readers-notifications__menu-top">
                                    <strong class="readers-notifications__heading">Уведомления</strong>
                                    <a href="{{ route('account') }}" class="readers-notifications__all-top">Все →</a>
                                </div>
                                @forelse ($unreadItems as $item)
                                    @if ($item->type === \App\Notifications\NewArticleNotification::class)
                                        <form method="post" action="{{ route('account.notifications.open', $item->id) }}" class="readers-notifications__entry">
                                            @csrf
                                            <span class="readers-notifications__kind">Новая статья</span>
                                            <button type="submit" class="readers-notifications__action">{{ $item->data['article_title'] ?? 'Открыть статью' }} ↗</button>
                                        </form>
                                    @else
                                        <a class="readers-notifications__entry" href="{{ route('account') }}">
                                            <span class="readers-notifications__kind">Проверка комментария</span>
                                            <strong class="readers-notifications__action">{{ $item->data['article_title'] ?? 'Посмотреть результат' }} ↗</strong>
                                        </a>
                                    @endif
                                @empty
                                    <p class="readers-notifications__empty">Новых уведомлений пока нет.</p>
                                @endforelse
                            </div>
                        </details>
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
                    <div class="navigation-primary">
                        <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home', 'galery') ? 'active' : '' }}" {{ request()->routeIs('home') ? 'aria-current=page' : '' }}>Главная</a>
                        <a href="{{ route('about') }}" class="nav-link {{ request()->routeIs('about') ? 'active' : '' }}">О нас</a>
                        <a href="{{ route('contacts') }}" class="nav-link {{ request()->routeIs('contacts') ? 'active' : '' }}">Контакты</a>
                        <a href="{{ route('news.index') }}" class="nav-link {{ request()->routeIs('news.index', 'news.show') ? 'active' : '' }}">Новости</a>
                    </div>
                    @auth
                        @can('create', App\Models\Article::class)
                            <div class="navigation-admin">
                                <span class="navigation-admin-label">МОДЕРАТОР</span>
                                <a href="{{ route('news.create') }}" class="nav-link nav-admin-create {{ request()->routeIs('news.create') ? 'active' : '' }}">+ Новая статья</a>
                                <a href="{{ route('news.manage') }}" class="nav-link {{ request()->routeIs('news.manage', 'news.edit') ? 'active' : '' }}">Управление</a>
                                @can('manage-comments')
                                    <a href="{{ route('comments.moderation') }}" class="nav-link {{ request()->routeIs('comments.moderation') ? 'active' : '' }}">Модерация</a>
                                @endcan
                            </div>
                        @endcan
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
    <div id="app" aria-label="Онлайн-оповещения о новых статьях"></div>
</body>
</html>
