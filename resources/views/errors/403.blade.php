@extends('layouts.app')
@section('title', 'Доступ запрещён')
@section('content')
<section class="news-admin container access-denied">
    <span class="eyebrow"><span class="eyebrow-dot"></span> ОШИБКА 403</span>
    <h1>Недостаточно <span class="serif-accent">прав.</span></h1>
    <p>{{ $exception->getMessage() !== 'This action is unauthorized.' ? $exception->getMessage() : 'Это действие доступно только модератору.' }}</p>
    <a href="{{ route('news.index') }}" class="button button-primary">Вернуться к новостям ↗</a>
</section>
@endsection
