@extends('layouts.app')

@section('title', 'Новости')

@section('content')
<section class="news-page container">
    <div class="news-heading">
        <div>
            <span class="eyebrow"><span class="eyebrow-dot"></span> СТАТЬИ И НОВОСТИ</span>
            <h1>Мои <span class="serif-accent">новости.</span></h1>
            <p>Здесь собраны записи из базы данных. Список формируется автоматически.</p>
        </div>
        <span class="news-tally">{{ $articles->total() }} / записей</span>
    </div>

    @if ($articles->isEmpty())
        <div class="news-empty">
            <span class="news-empty-symbol">✳</span>
            <h2>Пока без новостей</h2>
            <p>Новые записи появятся здесь после добавления данных в базу.</p>
        </div>
    @else
        <div class="news-grid">
            @foreach ($articles as $article)
                <article class="news-card">
                    <div class="news-card-top">
                        <span class="news-label">НОВОСТЬ · {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="news-decoration" aria-hidden="true">✳</span>
                    </div>
                    <time datetime="{{ $article->published_at->format('Y-m-d') }}">{{ $article->published_at->format('d.m.Y') }}</time>
                    <h2><a href="{{ route('news.show', $article) }}">{{ $article->title }}</a></h2>
                    <p>{{ $article->summary }}</p>
                    <a href="{{ route('news.show', $article) }}" class="news-read" aria-label="Читать статью {{ $article->title }}">Читать полностью <span aria-hidden="true">↗</span></a>
                </article>
            @endforeach
        </div>
    @endif
    @if ($articles->hasPages())
        {{ $articles->onEachSide(1)->links('vendor.pagination.pink') }}
    @endif
</section>
@endsection
