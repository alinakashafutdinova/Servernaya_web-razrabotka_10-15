@extends('layouts.app')

@section('title', 'Вход')

@section('content')
<section class="signup-page container account-page">
    <div class="signup-intro">
        <span class="eyebrow"><span class="eyebrow-dot"></span> ЛАБОРАТОРНЫЕ РАБОТЫ №1–9</span>
        <h1>Вход <span class="serif-accent">в аккаунт.</span></h1>
        <p>Войдите в аккаунт, чтобы получить доступ к функциям, соответствующим вашей роли.</p>
        <div class="signup-sticker" aria-hidden="true">
            <span class="signup-sticker-star">✳</span>
            <span>welcome<br><em>back.</em></span>
            <span class="signup-sticker-foot login-demo-credentials">эл. почта: lab1-9@mail.ru<br>пароль: moderator</span>
        </div>
    </div>
    <div class="signup-card">
        <div class="signup-card-header"><span class="signup-card-tag">ВХОД В ПРОФИЛЬ</span><span class="signup-card-icon" aria-hidden="true">✳</span></div>
        <h2>Авторизация<span>.</span></h2>
        <p class="signup-card-lead">Введите электронную почту и пароль для входа.</p>
        @if (session('status'))<div class="auth-notice" role="status">{{ session('status') }}</div>@endif
        <form method="post" action="{{ route('login.store') }}" class="signup-form">
            @csrf
            <div class="signup-field">
                <label for="login-email">Электронная почта <span>*</span></label>
                <input id="login-email" type="email" name="email" value="{{ old('email') }}" maxlength="254" autocomplete="email" placeholder="example@mail.ru" required @error('email') aria-invalid="true" @enderror>
                @error('email')<p class="signup-error">{{ $message }}</p>@enderror
            </div>
            <div class="signup-field">
                <label for="login-password">Пароль <span>*</span></label>
                <input id="login-password" type="password" name="password" autocomplete="current-password" placeholder="Ваш пароль" required @error('password') aria-invalid="true" @enderror>
                @error('password')<p class="signup-error">{{ $message }}</p>@enderror
            </div>
            <button class="button button-primary signup-submit" type="submit">Войти <span aria-hidden="true">↗</span></button>
        </form>
        <div class="signup-card-note">У вас нет аккаунта? <a class="auth-inline-link" href="{{ route('register') }}">Зарегистрироваться</a></div>
    </div>
</section>
@endsection
