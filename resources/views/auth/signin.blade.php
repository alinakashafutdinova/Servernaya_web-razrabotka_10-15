@extends('layouts.app')

@section('title', 'Регистрация')

@section('content')
<section class="signup-page container">
    <div class="signup-intro">
        <span class="eyebrow"><span class="eyebrow-dot"></span> ЛАБОРАТОРНЫЕ РАБОТЫ №1–9</span>
        <h1>Будем <span class="serif-accent">знакомы.</span></h1>
        <p>Здесь я работаю с формами, проверкой введённых данных и защитой запросов с помощью CSRF-токена.</p>
        <div class="signup-sticker" aria-hidden="true">
            <span class="signup-sticker-star">✳</span>
            <span>create<br><em>something</em><br>new.</span>
            <span class="signup-sticker-foot">PHP · Laravel · Blade</span>
        </div>
        <div class="signup-step"><span>01</span> Заполнить форму <span aria-hidden="true">↗</span></div>
        <div class="signup-step"><span>02</span> Проверить данные <span aria-hidden="true">↗</span></div>
        <div class="signup-step"><span>03</span> Получить JSON-ответ <span aria-hidden="true">↗</span></div>
    </div>

    <div class="signup-card">
        <div class="signup-card-header">
            <span class="signup-card-tag">НОВАЯ РЕГИСТРАЦИЯ</span>
            <span class="signup-card-icon" aria-hidden="true">✳</span>
        </div>
        <h2>Создать <span>профиль</span></h2>
        <p class="signup-card-lead">Введите данные для проверки серверной обработки формы.</p>

        <form method="post" action="{{ route('signin.registration') }}" class="signup-form">
            @csrf
            <div class="signup-field">
                <label for="name">Имя <span>*</span></label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" placeholder="Ваше имя" autocomplete="name" maxlength="80" required @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                @error('name')<p class="signup-error" id="name-error">{{ $message }}</p>@enderror
            </div>
            <div class="signup-field">
                <label for="email">Электронная почта <span>*</span></label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="example@mail.ru" autocomplete="email" maxlength="254" required @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                @error('email')<p class="signup-error" id="email-error">{{ $message }}</p>@enderror
            </div>
            <div class="signup-field">
                <label for="password">Пароль <span>*</span></label>
                <input id="password" name="password" type="password" placeholder="Не менее 8 символов" autocomplete="new-password" minlength="8" maxlength="72" required @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                @error('password')<p class="signup-error" id="password-error">{{ $message }}</p>@enderror
                <p class="signup-hint">От 8 до 72 символов. Пароль не отображается в ответе.</p>
            </div>
            <button class="button button-primary signup-submit" type="submit">Отправить данные <span aria-hidden="true">↗</span></button>
        </form>
        <div class="signup-card-note"><span aria-hidden="true">✳</span> Форма демонстрирует валидацию и возвращает JSON. Данные не сохраняются.</div>
    </div>
</section>
@endsection
