@extends('layouts.app')

@section('title', 'О нас')

@section('content')
<section class="inner-hero container">
    <div class="inner-copy">
        <span class="eyebrow"><span class="eyebrow-dot"></span> ЗНАКОМСТВО</span>
        <h1>Немного <span class="serif-accent">обо мне.</span></h1>
        <p class="inner-lead">Я Алина, учусь на направлении «Веб-технологии» в Московском Политехническом Университете.</p>
    </div>
    <div class="inner-deco" aria-hidden="true"><span class="deco-sun">✳</span><span class="deco-label">HELLO<br>THERE!</span></div>
</section>
<section class="container about-content">
    <div class="story-card">
        <div class="story-title"><span class="section-number">01 / ИСТОРИЯ</span><h2>Учусь создавать<br><em>что-то настоящее</em></h2></div>
        <div class="story-text"><p>Мне нравится, что веб-разработка объединяет логику и творчество: сначала продумываешь структуру, потом собираешь интерфейс, а в конце видишь готовый результат в браузере.</p><p>Сейчас я изучаю серверную часть сайтов, маршрутизацию и шаблоны. Этот проект объединяет лабораторные работы №1–9, в которых я применяю полученные знания на практике.</p></div>
    </div>
    <div class="about-facts">
        <div><span>НАПРАВЛЕНИЕ</span><strong>Веб-технологии</strong></div>
        <div><span>УНИВЕРСИТЕТ</span><strong>Московский Политех</strong></div>
        <div><span>ИЗУЧАЮ</span><strong>PHP · Laravel · Blade</strong></div>
    </div>
    <div class="bottom-cta"><span>Контактная информация</span><a class="button button-primary" href="{{ route('contacts') }}">Перейти к контактам <span aria-hidden="true">↗</span></a></div>
</section>
@endsection
