@extends('layouts.app')
@section('title', 'Новая статья')
@section('content')
<section class="news-admin container article-editor">
    <a class="news-back" href="{{ route('news.manage') }}">← Управление новостями</a>
    <span class="eyebrow"><span class="eyebrow-dot"></span> СОЗДАНИЕ</span>
    <h1>Новая <span class="serif-accent">статья.</span></h1>
    @include('news._form', ['action' => route('news.store'), 'method' => 'POST', 'buttonLabel' => 'Сохранить статью'])
</section>
@endsection
