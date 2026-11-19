<form class="article-form" method="post" action="{{ $action }}">
    @csrf
    @if ($method === 'PUT')
        @method('PUT')
    @endif

    <div class="article-field">
        <label for="title">Заголовок <span>*</span></label>
        <input id="title" name="title" type="text" required maxlength="160" value="{{ old('title', $article->title ?? '') }}" class="@error('title') has-error @enderror" aria-invalid="@error('title') true @else false @enderror">
        @error('title') <p class="field-error" role="alert">{{ $message }}</p> @enderror
    </div>
    <div class="article-field">
        <label for="summary">Краткое описание <span>*</span></label>
        <textarea id="summary" name="summary" rows="3" required maxlength="500" class="@error('summary') has-error @enderror">{{ old('summary', $article->summary ?? '') }}</textarea>
        @error('summary') <p class="field-error" role="alert">{{ $message }}</p> @enderror
    </div>
    <div class="article-field">
        <label for="content">Текст статьи <span>*</span></label>
        <textarea id="content" name="content" rows="10" required maxlength="20000" class="@error('content') has-error @enderror">{{ old('content', $article->content ?? '') }}</textarea>
        @error('content') <p class="field-error" role="alert">{{ $message }}</p> @enderror
    </div>
    <div class="article-form-row">
        <div class="article-field">
            <label for="published_at">Дата публикации <span>*</span></label>
            <input id="published_at" name="published_at" type="date" required value="{{ old('published_at', isset($article) ? $article->published_at->format('Y-m-d') : now()->format('Y-m-d')) }}" class="@error('published_at') has-error @enderror">
            @error('published_at') <p class="field-error" role="alert">{{ $message }}</p> @enderror
        </div>
        <label class="article-checkbox">
            <input type="hidden" name="is_published" value="0">
            <input type="checkbox" name="is_published" value="1" {{ old('is_published', $article->is_published ?? true) ? 'checked' : '' }}>
            Опубликовать на сайте
        </label>
    </div>
    @error('is_published') <p class="field-error" role="alert">{{ $message }}</p> @enderror
    <div class="article-form-actions">
        <button type="submit" class="button button-primary">{{ $buttonLabel }}</button>
        <a href="{{ route('news.manage') }}" class="admin-link">Отмена</a>
    </div>
</form>
