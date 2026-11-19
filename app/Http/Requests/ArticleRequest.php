<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Article;
use Illuminate\Support\Facades\Gate;

class ArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $article = $this->route('article');
        Gate::authorize($article instanceof Article ? 'update' : 'create', $article ?? Article::class);
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:160'],
            'summary' => ['required', 'string', 'min:10', 'max:500'],
            'content' => ['required', 'string', 'min:10', 'max:20000'],
            'published_at' => ['required', 'date_format:Y-m-d'],
            'is_published' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'заголовок',
            'summary' => 'краткое описание',
            'content' => 'текст статьи',
            'published_at' => 'дата публикации',
            'is_published' => 'статус публикации',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Заполните поле «:attribute».',
            'string' => 'Поле «:attribute» должно содержать текст.',
            'min' => 'Поле «:attribute» слишком короткое (минимум :min символов).',
            'max' => 'Поле «:attribute» слишком длинное (максимум :max символов).',
            'date_format' => 'Укажите дату в формате ГГГГ-ММ-ДД.',
            'boolean' => 'Неверное значение статуса публикации.',
        ];
    }
}
