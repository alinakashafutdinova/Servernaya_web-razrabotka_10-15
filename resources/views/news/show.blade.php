@extends('layouts.app')

@section('title', $article->title)

@section('content')
<section class="news-story container">
    <a class="news-back" href="{{ route('news.index') }}"><span aria-hidden="true">←</span> Ко всем новостям</a>

    <div class="news-story-grid">
        <article class="news-story-content">
            <span class="eyebrow"><span class="eyebrow-dot"></span> СТАТЬЯ ИЗ БАЗЫ ДАННЫХ</span>
            <h1>{{ $article->title }}</h1>
            <time datetime="{{ $article->published_at->format('Y-m-d') }}">{{ $article->published_at->format('d.m.Y') }}</time>
            <p class="news-story-lead">{{ $article->summary }}</p>
            <div class="news-story-body">{{ $article->content }}</div>
            <a href="{{ route('news.index') }}" class="button button-primary news-story-all">Все новости <span aria-hidden="true">↗</span></a>
        </article>

        <aside class="news-story-aside" aria-label="Дополнительно о статьях">
            <div class="news-story-visual">
                <img src="{{ asset('images/news-illustration.svg') }}" width="760" height="660" alt="Иллюстрация веб-страницы с розовыми акцентами" loading="lazy">
                <div class="news-visual-caption">
                    <span class="news-visual-label">МОИ ЗАМЕТКИ</span>
                    <strong>О веб-разработке и не только.</strong>
                    <p>Собираю знания в одном месте.</p>
                </div>
            </div>

            @if ($relatedArticles->isNotEmpty())
                <div class="news-related">
                    <h2>Ещё почитать <span aria-hidden="true">↗</span></h2>
                    <ul>
                        @foreach ($relatedArticles as $relatedArticle)
                            <li>
                                <a href="{{ route('news.show', $relatedArticle) }}">
                                    <span class="news-related-title">{{ $relatedArticle->title }}</span>
                                    <time datetime="{{ $relatedArticle->published_at->format('Y-m-d') }}">{{ $relatedArticle->published_at->format('d.m.Y') }}</time>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </aside>
    </div>

    <section class="comments-section" id="comments" aria-labelledby="comments-title">
        <div class="comments-head">
            <span class="eyebrow"><span class="eyebrow-dot"></span> ОБСУЖДЕНИЕ</span>
            <h2 id="comments-title">Комментарии <span class="comments-count">{{ $comments->count() }}</span></h2>
        </div>

        @if (session('status'))
            <div class="admin-notice" role="status">{{ session('status') }}</div>
        @endif

        @auth
            @can('create', [App\Models\Comment::class, $article])
                <form class="comment-form" action="{{ route('comments.store', $article) }}" method="post">
                    @csrf
                    <label for="comment-body">Оставить комментарий</label>
                    <textarea id="comment-body" name="body" maxlength="1000" minlength="3" required placeholder="Поделитесь мнением о статье...">{{ old('body') }}</textarea>
                    @error('body')<p class="form-error" role="alert">{{ $message }}</p>@enderror
                    <button type="submit" class="button button-primary">Отправить комментарий ↗</button>
                </form>
            @endcan
        @else
            <p class="comment-hint">Чтобы добавить комментарий, <a href="{{ route('login') }}">войдите в аккаунт</a>.</p>
        @endauth

        @forelse ($comments as $comment)
            <article class="comment-item" id="comment-{{ $comment->id }}">
                <div class="comment-meta">
                    <strong>{{ $comment->user->name }}</strong>
                    <time datetime="{{ $comment->created_at->toIso8601String() }}">{{ $comment->created_at->format('d.m.Y H:i') }}</time>
                </div>
                <p>{{ $comment->body }}</p>

            </article>
        @empty
            <p class="comment-hint">Комментариев пока нет. Можно начать обсуждение.</p>
        @endforelse
    </section>

</section>
@endsection
