@extends('layouts.app')

@section('title', 'Мой профиль и уведомления')

@section('content')
<section class="account-dashboard container">
    <div class="profile-header">
        <span class="eyebrow"><span class="eyebrow-dot"></span> ВЫ В СВОЁМ АККАУНТЕ</span>
        <h1>Добро пожаловать, <span class="serif-accent">{{ auth()->user()->name }}!</span></h1>
        <p>Здесь можно увидеть результат проверки ваших комментариев.</p>
    </div>
    <div class="profile-summary">
        <div><span>ПРОФИЛЬ</span><strong>{{ auth()->user()->name }}</strong></div>
        <div><span>ЭЛЕКТРОННАЯ ПОЧТА</span><strong>{{ auth()->user()->email }}</strong></div>
        <div><span>РОЛЬ</span><strong>{{ auth()->user()->isModerator() ? 'Модератор' : 'Читатель' }}</strong></div>
    </div>
    <div class="profile-notifications-head">
        <div>
            <h2>Мои <span class="serif-accent">уведомления.</span></h2>
            <p>Здесь сохраняются новые статьи и результаты проверки ваших комментариев.</p>
        </div>
        @if (auth()->user()->unreadNotifications()->exists())
            <form method="post" action="{{ route('account.notifications.read') }}">
                @csrf
                <button type="submit" class="button button-secondary">Отметить как прочитанные</button>
            </form>
        @endif
    </div>
    @if (session('status'))<div class="admin-notice" role="status">{{ session('status') }}</div>@endif
    <div class="profile-notifications-list">
        @forelse ($notifications as $notification)
            <article class="profile-notification {{ $notification->read_at === null ? 'is-unread' : '' }}">
                <div class="profile-notification-top">
                    @if ($notification->type === \App\Notifications\NewArticleNotification::class)
                        <span class="notification-state is-approved">✳ Новая публикация</span>
                    @else
                        <span class="notification-state {{ ($notification->data['status'] ?? '') === 'approved' ? 'is-approved' : 'is-rejected' }}">
                            {{ ($notification->data['status'] ?? '') === 'approved' ? '✓ Комментарий опубликован' : 'Комментарий отклонён' }}
                        </span>
                    @endif
                    <time datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->format('d.m.Y, H:i') }}</time>
                </div>
                <h3>{{ $notification->data['article_title'] ?? 'Статья' }}</h3>
                @if ($notification->type === \App\Notifications\NewArticleNotification::class)
                    <p>{{ $notification->data['message'] ?? 'Появилась новая статья.' }}</p>
                    <form method="post" action="{{ route('account.notifications.open', $notification->id) }}">
                        @csrf
                        <button class="readers-notifications__action" type="submit">Открыть статью ↗</button>
                    </form>
                @else
                    <p>{{ $notification->data['comment_excerpt'] ?? '' }}</p>
                    @if (($notification->data['status'] ?? '') === 'approved')
                        <a href="{{ route('news.show', ['article' => $notification->data['article_id']]) }}#comments">Посмотреть комментарий ↗</a>
                    @endif
                @endif
            </article>
        @empty
            <div class="profile-notification-empty">Пока нет уведомлений. Новости и результаты проверки комментариев появятся здесь.</div>
        @endforelse
    </div>
    {{ $notifications->links('vendor.pagination.pink') }}
</section>
@endsection
