@extends('layouts.app')

@section('title', 'Модерация комментариев')

@section('content')
<section class="moderation-page container">
    <a class="news-back" href="{{ route('news.index') }}">← Ко всем новостям</a>

    <div class="moderation-heading">
        <div>
            <span class="eyebrow"><span class="eyebrow-dot"></span> ПАНЕЛЬ МОДЕРАТОРА</span>
            <h1>Модерация <span class="serif-accent">комментариев.</span></h1>
            <p>Здесь находятся только новые комментарии, ожидающие проверки. Принятые появятся под соответствующими статьями.</p>
        </div>
        <span class="moderation-total">На проверке: {{ $comments->total() }}</span>
    </div>

    @if (session('status'))
        <div class="admin-notice" role="status">{{ session('status') }}</div>
    @endif

    @forelse ($comments as $comment)
        <article class="moderation-card">
            <div class="moderation-card-header">
                <span class="moderation-pending">Ожидает модерации</span>
                <time datetime="{{ $comment->created_at->toIso8601String() }}">{{ $comment->created_at->format('d.m.Y, H:i') }}</time>
            </div>
            <h2>{{ $comment->user->name }}</h2>
            <p class="moderation-article">К статье: <a href="{{ route('news.show', $comment->article) }}">{{ $comment->article->title }}</a></p>
            <p class="moderation-comment">{{ $comment->body }}</p>
            <div class="moderation-actions">
                <form method="post" action="{{ route('comments.approve', $comment) }}">
                    @csrf
                    @method('PATCH')
                    <button class="button button-primary" type="submit">Принять комментарий ✓</button>
                </form>
                <form method="post" action="{{ route('comments.reject', $comment) }}" onsubmit="return confirm('Отклонить и удалить комментарий?');">
                    @csrf
                    @method('DELETE')
                    <button class="button moderation-reject" type="submit">Отклонить</button>
                </form>
            </div>
        </article>
    @empty
        <div class="moderation-empty">
            <span aria-hidden="true">✳</span>
            <h2>Новых комментариев пока нет</h2>
            <p>Все комментарии проверены. Новые сообщения появятся здесь автоматически.</p>
        </div>
    @endforelse

    {{ $comments->links('vendor.pagination.pink') }}
</section>
@endsection
