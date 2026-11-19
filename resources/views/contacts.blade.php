@extends('layouts.app')

@section('title', 'Контакты')

@section('content')
<section class="inner-hero container contact-hero">
    <div class="inner-copy">
        <span class="eyebrow"><span class="eyebrow-dot"></span> НА СВЯЗИ</span>
        <h1>Контактная <span class="serif-accent">информация.</span></h1>
        <p class="inner-lead">Здесь собрала ссылки, по которым можно посмотреть мои проекты и узнать, над чем я работаю.</p>
    </div>
    <div class="contact-art" aria-hidden="true"><span>↗</span></div>
</section>
<section class="container contacts-section">
    <div class="contacts-header"><span class="section-number">01 / КОНТАКТЫ</span><p>Все ссылки в одном месте</p></div>
    <div class="contact-list">
        @foreach ($contacts as $contact)
            <a class="contact-card" href="{{ $contact['url'] }}" target="_blank" rel="noopener noreferrer">
                <span class="contact-index">{{ $contact['icon'] }}</span>
                <span class="contact-main"><strong>{{ $contact['title'] }}</strong><span>{{ $contact['description'] }}</span></span>
                <span class="contact-value">{{ $contact['value'] }}</span>
                <span class="contact-arrow" aria-hidden="true">↗</span>
            </a>
        @endforeach
    </div>
    <div class="contact-note"><span>✳</span><p>Этот список формируется из массива в маршруте. Чтобы добавить новый контакт, достаточно добавить ещё один элемент в массив.</p></div>
</section>
@endsection
