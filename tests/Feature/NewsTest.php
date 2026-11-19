<?php

namespace Tests\Feature;

use App\Models\Article;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_articles_table_is_created_by_migration(): void
    {
        $this->assertTrue(Schema::hasTable('articles'));
        $this->assertTrue(Schema::hasColumns('articles', ['title', 'summary', 'content', 'published_at', 'is_published']));
    }

    public function test_factory_creates_article_with_text_and_date(): void
    {
        $article = Article::factory()->create();

        $this->assertNotEmpty($article->title);
        $this->assertNotEmpty($article->summary);
        $this->assertNotEmpty($article->content);
        $this->assertNotNull($article->published_at);
        $this->assertTrue($article->is_published);
        $this->assertDatabaseHas('articles', ['id' => $article->id]);
    }

    public function test_seeder_creates_twelve_articles(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->assertSame(12, Article::count());
    }

    public function test_news_index_lists_published_articles_newest_first(): void
    {
        Article::factory()->create(['title' => 'Старая запись', 'published_at' => '2026-01-01 12:00:00']);
        Article::factory()->create(['title' => 'Новая запись', 'published_at' => '2026-10-01 12:00:00']);
        Article::factory()->unpublished()->create(['title' => 'Черновик']);

        $response = $this->get('/news')->assertOk()->assertViewIs('news.index');
        $response->assertSeeText('Новая запись')->assertSeeText('Старая запись')->assertDontSeeText('Черновик');
        $this->assertLessThan(strpos($response->getContent(), 'Старая запись'), strpos($response->getContent(), 'Новая запись'));
    }

    public function test_news_index_handles_empty_database(): void
    {
        $this->get('/news')->assertOk()->assertSeeText('Пока без новостей');
    }

    public function test_article_detail_displays_full_content(): void
    {
        $article = Article::factory()->create(['title' => 'Тестовая статья', 'content' => 'Полный текст тестовой статьи']);
        $this->get('/news/'.$article->id)->assertOk()->assertViewIs('news.show')
            ->assertSeeText('Тестовая статья')->assertSeeText('Полный текст тестовой статьи');
    }

    public function test_unpublished_and_unknown_articles_return_404(): void
    {
        $article = Article::factory()->unpublished()->create();
        $this->get('/news/'.$article->id)->assertNotFound();
        $this->get('/news/999999')->assertNotFound();
        $this->get('/news/not-a-number')->assertNotFound();
    }

    public function test_html_from_database_is_escaped(): void
    {
        Article::factory()->create(['title' => '<script>alert(1)</script>', 'summary' => '<img src=x onerror=alert(1)>']);
        $this->get('/news')->assertOk()->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<img src=x onerror=alert(1)>', false);
    }

    public function test_previous_labs_still_work(): void
    {
        $this->get('/')->assertOk()->assertSeeText('Алина Кашафутдинова');
        $this->get('/about')->assertOk();
        $this->get('/contacts')->assertOk();
        $this->get('/signin')->assertOk();
        $this->get('/galery/1')->assertOk();
    }
}
