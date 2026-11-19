@extends('layouts.app')

@section('title', 'Главная')

@section('content')
<section class="hero container">
    <div class="hero-content">
        <span class="eyebrow"><span class="eyebrow-dot"></span> КУРСОВОЙ ПРОЕКТ · №10–15</span>
        <h1><span class="hero-hello">Здравствуйте!</span><br><span class="hero-name">Я Алина<span class="pink-period">.</span></span><br><span class="hero-light">Добро пожаловать!</span></h1>
        @if (session('welcome'))
            <p class="hero-welcome-notice" role="status">{{ session('welcome') }}</p>
        @endif
        @auth
            <div class="hero-login-state" role="status"><span class="hero-online-dot" aria-hidden="true"></span> Вы вошли как <strong>{{ auth()->user()->name }}</strong> · <a href="{{ route('account') }}">Мой профиль</a></div>
        @endauth
        <p class="hero-description">Этот курсовой проект продолжает сайт лабораторных №1–9. В работах №10–15 добавляются очереди задач и новый серверный функционал.</p>
        <div class="hero-actions">
            <a class="button button-primary" href="{{ route('about') }}">Узнать обо мне <span aria-hidden="true">↗</span></a>
            <a class="button button-secondary" href="#works">Смотреть работы <span aria-hidden="true">↗</span></a>
        </div>
        <div class="hero-micro"><span class="micro-line"></span> Алина Кашафутдинова</div>
    </div>
    <div class="hero-visual" aria-hidden="true">
        <div class="visual-grid"></div>
        <div class="poster-card">
            <div class="poster-top"><span>WEB / 001</span><span class="poster-sparkle">✳</span></div>
            <div class="poster-illustration"><span class="abstract-ring"></span><span class="abstract-shape"></span><span class="abstract-small"></span></div>
            <div class="poster-bottom"><span>learning by<br>creating.</span><span class="poster-arrow">↗</span></div>
        </div>
        <span class="visual-orbit orbit-one">✳</span>
        <span class="visual-orbit orbit-two">✴</span>
        <div class="visual-sticker">made with<br><b>curiosity ♡</b></div>
    </div>
</section>

<section class="highlights">
    <div class="container highlights-inner">
        <div class="section-heading"><span class="section-number">01 — 03</span><h2>С чего всё начинается</h2><p>Три вещи, которые для меня важны в разработке.</p></div>
        <div class="features">
            <article class="feature-card"><span class="feature-icon">✦</span><span class="feature-no">01</span><h3>Идея</h3><p>У каждого проекта должна быть понятная цель и своя маленькая история.</p></article>
            <article class="feature-card"><span class="feature-icon">◌</span><span class="feature-no">02</span><h3>Детали</h3><p>Люблю, когда сайт не только работает, но и выглядит аккуратно.</p></article>
            <article class="feature-card"><span class="feature-icon">↗</span><span class="feature-no">03</span><h3>Развитие</h3><p>С каждой новой работой осваиваю ещё что-то полезное.</p></article>
        </div>
    </div>
</section>

<section class="projects-section" id="works">
    <div class="container">
        <div class="projects-intro">
            <div>
                <span class="eyebrow"><span class="eyebrow-dot"></span> ДАННЫЕ ИЗ JSON</span>
                <h2>Статьи <span class="serif-accent">из файла</span></h2>
                <p>Небольшая подборка статей. Заголовки, даты, описания и изображения автоматически загружаются из JSON. Нажмите на превью, чтобы открыть полноразмерное изображение.</p>
            </div>
            <span class="projects-count">{{ count($articles) }} / статей</span>
        </div>
        @if (count($articles))
            <div class="projects-grid">
                @foreach ($articles as $article)
                    <article class="project-card">
                        <a href="{{ route('galery', ['id' => $article['id']]) }}" class="project-image-link" aria-label="Открыть галерею: {{ $article['name'] }}">
                            <img src="{{ asset($article['preview_image']) }}" alt="Превью статьи «{{ $article['name'] }}»" loading="lazy">
                            <span class="project-image-badge">Смотреть <span aria-hidden="true">↗</span></span>
                        </a>
                        <div class="project-meta"><span>Статья {{ str_pad($article['id'], 2, '0', STR_PAD_LEFT) }}</span><time>{{ $article['date'] }}</time></div>
                        <h3><a href="{{ route('galery', ['id' => $article['id']]) }}">{{ $article['name'] }}</a></h3>
                        <p>{{ $article['shortDesc'] ?? $article['desc'] }}</p>
                    </article>
                @endforeach
            </div>
        @else
            <p class="empty-state">Статьи пока не добавлены.</p>
        @endif
    </div>
</section>

@endsection
