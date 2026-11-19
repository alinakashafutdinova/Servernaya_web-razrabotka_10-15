@extends('layouts.app')

@section('title', $article['name'])

@section('content')
<section class="gallery-page container">
    <nav class="breadcrumbs" aria-label="Навигационная цепочка">
        <a href="{{ route('home') }}">Главная</a><span aria-hidden="true">/</span><span>Галерея</span>
    </nav>
    <div class="gallery-heading">
        <div>
            <span class="eyebrow"><span class="eyebrow-dot"></span> ГАЛЕРЕЯ / {{ $article['date'] }}</span>
            <h1>{{ $article['name'] }}<span class="pink-period">.</span></h1>
            <p>{{ $article['desc'] }}</p>
        </div>
        <a class="gallery-back" href="{{ route('home') }}#works"><span aria-hidden="true">←</span> Все статьи</a>
    </div>
    <figure class="gallery-figure">
        <img src="{{ asset($article['full_image']) }}" alt="Полное изображение статьи «{{ $article['name'] }}»">
        <figcaption>Полноразмерное изображение · {{ $article['date'] }}</figcaption>
    </figure>
    <div class="gallery-footer-link"><a class="button button-primary" href="{{ route('home') }}#works">Вернуться к статьям <span aria-hidden="true">↗</span></a></div>
</section>
@endsection
