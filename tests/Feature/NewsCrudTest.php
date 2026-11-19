<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class NewsCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $role = \App\Models\Role::firstOrCreate(['slug' => 'moderator'], ['name' => 'Модератор']);
        $this->actingAs(User::create([
            'role_id' => $role->id,
            'name' => 'Алина',
            'email' => 'admin@example.test',
            'password' => \Illuminate\Support\Facades\Hash::make('StrongPass123!'),
        ]));
    }

    private function validArticle(): array
    {
        return [
            'title' => 'Новая статья об интерфейсах',
            'summary' => 'Краткое содержание статьи для проверки.',
            'content' => 'Подробный текст статьи с полезными пояснениями.',
            'published_at' => '2026-11-18',
            'is_published' => '1',
        ];
    }

    public function test_news_page_shows_six_articles_and_page_links(): void
    {
        Article::factory()->count(13)->create(['published_at' => '2026-11-18 12:00:00']);
        $response = $this->get('/news')->assertOk();
        $this->assertCount(6, $response->viewData('articles')->items());
        $response->assertSee('page=2', false)->assertSee('page=3', false);
        $this->assertSame(13, $response->viewData('articles')->total());

        $second = $this->get('/news?page=2')->assertOk();
        $this->assertCount(6, $second->viewData('articles')->items());
        $third = $this->get('/news?page=3')->assertOk();
        $this->assertCount(1, $third->viewData('articles')->items());
    }

    public function test_drafts_do_not_appear_on_public_pages(): void
    {
        Article::factory()->count(6)->create(['title' => 'Опубликованная новость']);
        Article::factory()->unpublished()->create(['title' => 'Секретный черновик']);
        $this->get('/news')->assertOk()->assertDontSeeText('Секретный черновик');
    }

    public function test_management_shows_drafts_and_paginates(): void
    {
        Article::factory()->count(9)->create();
        Article::factory()->unpublished()->create(['title' => 'Мой черновик']);
        $response = $this->get('/management')->assertOk()->assertViewIs('news.manage');
        $this->assertCount(8, $response->viewData('articles')->items());
        $response->assertSeeText('Мой черновик')->assertSeeText('Черновик')->assertSee('page=2', false);
        $response2 = $this->get('/management?page=2')->assertOk();
        $this->assertCount(2, $response2->viewData('articles')->items());
    }

    public function test_create_form_has_csrf_token_and_required_fields(): void
    {
        $this->get('/news/create')->assertOk()->assertViewIs('news.create')
            ->assertSee('name="_token"', false)
            ->assertSee('name="title"', false)
            ->assertSee('name="summary"', false)
            ->assertSee('name="content"', false)
            ->assertSee('name="published_at"', false)
            ->assertSee('name="is_published"', false);
    }

    public function test_store_creates_new_article(): void
    {
        $response = $this->post('/news', $this->validArticle());
        $response->assertRedirect(route('news.manage'))->assertSessionHas('status');
        $this->assertDatabaseHas('articles', ['title' => 'Новая статья об интерфейсах', 'is_published' => 1]);
    }

    public function test_store_unchecked_checkbox_creates_draft(): void
    {
        $data = $this->validArticle();
        $data['is_published'] = '0';
        $this->post('/news', $data)->assertRedirect(route('news.manage'));
        $article = Article::firstOrFail();
        $this->assertFalse($article->is_published);
        $this->get('/news/'.$article->id)->assertOk()->assertSeeText($article->title);
    }

    public function test_store_validation_rejects_wrong_data_and_preserves_input(): void
    {
        $this->from('/news/create')->post('/news', [
            'title' => 'a',
            'summary' => 'small',
            'content' => 'short',
            'published_at' => '2026-02-30',
            'is_published' => 'wrong',
        ])->assertRedirect('/news/create')->assertSessionHasErrors(['title', 'summary', 'content', 'published_at', 'is_published']);
        $this->assertSame(0, Article::count());
    }

    public function test_update_edits_article_and_can_unpublish(): void
    {
        $article = Article::factory()->create();
        $this->get('/news/'.$article->id.'/edit')->assertOk()->assertViewIs('news.edit')
            ->assertSee('name="_method" value="PUT"', false);
        $data = $this->validArticle();
        $data['is_published'] = '0';
        $this->put('/news/'.$article->id, $data)->assertRedirect(route('news.manage'));
        $article->refresh();
        $this->assertSame($data['title'], $article->title);
        $this->assertFalse($article->is_published);
    }

    public function test_update_invalid_data_does_not_change_article(): void
    {
        $article = Article::factory()->create(['title' => 'Неизменённый заголовок']);
        $this->from('/news/'.$article->id.'/edit')->put('/news/'.$article->id, [
            'title' => '', 'summary' => '', 'content' => '', 'published_at' => 'wrong',
        ])->assertSessionHasErrors(['title', 'summary', 'content', 'published_at']);
        $this->assertSame('Неизменённый заголовок', $article->fresh()->title);
    }

    public function test_delete_article(): void
    {
        $article = Article::factory()->create();
        $this->get('/management')->assertOk()->assertSee('name="_method" value="DELETE"', false);
        $this->delete('/news/'.$article->id)->assertRedirect(route('news.manage'))->assertSessionHas('status');
        $this->assertDatabaseMissing('articles', ['id' => $article->id]);
    }

    public function test_methods_and_csrf_middleware(): void
    {
        foreach (['news.store' => 'POST', 'news.update' => 'PUT', 'news.destroy' => 'DELETE'] as $routeName => $method) {
            $route = Route::getRoutes()->getByName($routeName);
            $this->assertNotNull($route);
            $this->assertContains($method, $route->methods());
            $this->assertContains('web', $route->gatherMiddleware());
        }
    }

    public function test_strange_urls_and_html_are_handled_safely(): void
    {
        $this->get('/news/invalid/edit')->assertNotFound();
        $this->put('/news/999999', $this->validArticle())->assertNotFound();
        $this->delete('/news/999999')->assertNotFound();
        $data = $this->validArticle();
        $data['title'] = '<script>alert(5)</script>';
        $this->post('/news', $data)->assertRedirect();
        $this->get('/management')->assertOk()
            ->assertDontSee('<script>alert(5)</script>', false)
            ->assertSee('&lt;script&gt;alert(5)&lt;/script&gt;', false);
    }

    public function test_previous_lab_routes_remain_available(): void
    {
        $this->get('/')->assertOk();
        $this->get('/about')->assertOk();
        $this->get('/contacts')->assertOk();
        $this->get('/signin')->assertOk();
        $this->get('/galery/1')->assertOk();
    }
}
