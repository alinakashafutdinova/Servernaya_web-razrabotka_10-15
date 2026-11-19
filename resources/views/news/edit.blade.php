@extends('layouts.app')
@section('title', 'Редактирование статьи')
@section('content')
<section class="news-admin container article-editor">
    <a class="news-back" href="{{ route('news.manage') }}">← Управление новостями</a>
    <span class="eyebrow"><span class="eyebrow-dot"></span> РЕДАКТИРОВАНИЕ</span>
    <h1>Изменить <span class="serif-accent">статью.</span></h1>
    @include('news._form', ['action' => route('news.update', $article), 'method' => 'PUT', 'buttonLabel' => 'Сохранить изменения'])
</section>
@endsection
