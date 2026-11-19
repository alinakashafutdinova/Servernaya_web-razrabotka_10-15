<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use JsonException;

class MainController extends Controller
{
    public function index(): View
    {
        return view('home', ['articles' => $this->getArticles()]);
    }

    public function gallery(int $id): View
    {
        $article = collect($this->getArticles())->firstWhere('id', $id);

        abort_if($article === null, 404);

        return view('galery', ['article' => $article]);
    }

    private function getArticles(): array
    {
        $path = public_path('articles.json');

        if (!is_file($path) || !is_readable($path)) {
            return [];
        }

        try {
            $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            return [];
        }

        if (!is_array($data) || array_keys($data) !== array_keys(array_values($data))) {
            return [];
        }

        $articles = [];

        foreach ($data as $item) {
            if (!is_array($item)) {
                continue;
            }

            foreach (['date', 'name', 'desc', 'preview_image', 'full_image'] as $field) {
                if (!isset($item[$field]) || !is_string($item[$field])) {
                    continue 2;
                }
            }

            if (!preg_match('/^[a-zA-Z0-9_-]+\.(?:jpe?g|png|webp)$/i', $item['preview_image'])
                || !preg_match('/^[a-zA-Z0-9_-]+\.(?:jpe?g|png|webp)$/i', $item['full_image'])) {
                continue;
            }

            $item['id'] = count($articles) + 1;
            $articles[] = $item;
        }

        return $articles;
    }
}
