<?php

namespace Tests\Feature;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_has_illustration_and_related_articles(): void
    {
        $main = Article::factory()->create(['title' => 'Открытая статья']);
        $other = Article::factory()->create(['title' => 'Дополнительная статья']);
        Article::factory()->unpublished()->create(['title' => 'Скрытая статья']);

        $this->get('/news/'.$main->id)
            ->assertOk()
            ->assertSee('news-story-grid')
            ->assertSee('news-illustration.svg')
            ->assertSeeText('Дополнительная статья')
            ->assertDontSeeText('Скрытая статья')
            ->assertSee(route('news.show', $other), false);
    }

    public function test_article_does_not_link_to_itself(): void
    {
        $main = Article::factory()->create(['title' => 'Единственная статья']);

        $this->get('/news/'.$main->id)
            ->assertOk()
            ->assertDontSee('news-related-title');
    }
}
