@extends('layouts.app')

@section('title', 'Управление новостями')

@section('content')
<section class="news-admin container">
    <div class="admin-heading">
        <div>
            <span class="eyebrow"><span class="eyebrow-dot"></span> РЕДАКТОР СТАТЕЙ</span>
            <h1>Управление <span class="serif-accent">новостями.</span></h1>
            <p>Добавляю статьи, меняю содержание и выбираю, какие записи показывать на сайте.</p>
        </div>
        <a class="button button-primary" href="{{ route('news.create') }}">+ Новая статья</a>
    </div>

    <form method="post" action="{{ route('reports.daily.send') }}" class="admin-report-form">
        @csrf
        <button type="submit" class="button button-secondary">
            Отправить статистику за сегодня ✉
        </button>
        <p>ЛР №14 · просмотры новостей и новые комментарии за день. Повторная рассылка за сегодня пропускается.</p>
    </form>

    @if (session('status'))
        <div class="admin-notice" role="status">{{ session('status') }}</div>
    @endif

    @if ($articles->isEmpty())
        <div class="news-empty"><h2>Записей пока нет</h2><p>Нажмите «Новая статья», чтобы добавить первую запись.</p></div>
    @else
        <div class="admin-list">
            @foreach ($articles as $article)
                <article class="admin-item">
                    <div class="admin-item-info">
                        <span class="admin-meta">№ {{ $article->id }} · {{ $article->published_at->format('d.m.Y') }}</span>
                        <h2>{{ $article->title }}</h2>
                        <p>{{ $article->summary }}</p>
                        <span class="admin-status {{ $article->is_published ? 'is-public' : 'is-draft' }}">{{ $article->is_published ? 'Опубликовано' : 'Черновик' }}</span>
                    </div>
                    <div class="admin-actions">
                        @if ($article->is_published)
                            <a href="{{ route('news.show', $article) }}" class="admin-link">Просмотр</a>
                        @endif
                        <a href="{{ route('news.edit', $article) }}" class="admin-link">Изменить</a>
                        <form method="post" action="{{ route('news.destroy', $article) }}" onsubmit="return confirm('Удалить эту статью?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="admin-link danger-link">Удалить</button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>
        @if ($articles->hasPages())
            {{ $articles->onEachSide(1)->links('vendor.pagination.pink') }}
        @endif
    @endif
</section>
@endsection
