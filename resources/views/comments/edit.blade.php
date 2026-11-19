@extends('layouts.app')
@section('title', 'Изменить комментарий')
@section('content')
<section class="news-admin container article-editor">
    <a class="news-back" href="{{ route('news.show', $comment->article) }}">← Вернуться к статье</a>
    <span class="eyebrow"><span class="eyebrow-dot"></span> МОДЕРАЦИЯ</span>
    <h1>Изменить <span class="serif-accent">комментарий.</span></h1>
    <form class="comment-form" method="post" action="{{ route('comments.update', $comment) }}">
        @csrf
        @method('PUT')
        <label for="body">Текст комментария</label>
        <textarea id="body" name="body" required minlength="3" maxlength="1000">{{ old('body', $comment->body) }}</textarea>
        @error('body') <p role="alert" class="form-error">{{ $message }}</p> @enderror
        <button type="submit" class="button button-primary">Сохранить изменения</button>
    </form>
</section>
@endsection
