<?php

namespace Tests\Feature;

use App\Events\NewArticleEvent;
use App\Mail\ArticleCreatedMail;
use App\Models\Article;
use App\Models\ArticleView;
use App\Models\Role;
use App\Models\User;
use App\Notifications\NewArticleNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RestApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Event::fake([NewArticleEvent::class]);
    }

    private function person(string $role, string $email): User
    {
        $model = Role::firstOrCreate(
            ['slug' => $role],
            ['name' => $role === Role::MODERATOR ? 'Модератор' : 'Читатель']
        );

        return User::create([
            'role_id' => $model->id,
            'name' => 'Пользователь для API',
            'email' => $email,
            'password' => Hash::make('ValidPassword2026!'),
        ]);
    }

    private function moderatorToken(string $email = 'rest-moderator@example.test'): string
    {
        return $this->person(Role::MODERATOR, $email)
            ->createToken('Test REST API')->plainTextToken;
    }

    private function articleData(string $title = 'Тестовая статья для API', bool $published = true): array
    {
        return [
            'title' => $title,
            'summary' => 'Проверка REST API на реальных HTTP-запросах.',
            'content' => 'Полное описание статьи, которое сохраняется в базе данных.',
            'published_at' => '2026-11-20',
            'is_published' => $published,
        ];
    }

    public function test_anonymous_can_read_paginated_json_but_not_drafts(): void
    {
        Article::factory()->count(8)->create();
        $draft = Article::factory()->unpublished()->create();

        $page = $this->getJson('/api/articles?page=2')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.total', 8)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['id', 'title', 'summary', 'is_published']], 'meta', 'links']);

        $this->assertNull($page->json('links.next'));
        $this->getJson('/api/articles/'.$draft->id)->assertNotFound();
        $this->getJson('/api/articles/999999')->assertNotFound();
    }

    public function test_public_article_json_contains_only_approved_comments_and_counts_as_view(): void
    {
        $reader = $this->person(Role::READER, 'comment-reader-rest@example.test');
        $article = Article::factory()->create();

        $approved = $article->comments()->create([
            'user_id' => $reader->id, 'body' => 'Одобренный API-комментарий',
        ]);
        $approved->is_approved = true;
        $approved->save();

        $article->comments()->create([
            'user_id' => $reader->id, 'body' => 'Скрытый комментарий для модератора',
        ]);

        $this->getJson('/api/articles/'.$article->id)
            ->assertOk()
            ->assertJsonPath('data.id', $article->id)
            ->assertJsonPath('data.comments.0.body', 'Одобренный API-комментарий')
            ->assertJsonPath('data.comments.0.author.name', $reader->name)
            ->assertJsonCount(1, 'data.comments')
            ->assertDontSee('Скрытый комментарий для модератора')
            ->assertDontSee($reader->email)
            ->assertDontSee('password');

        $this->assertSame(1, ArticleView::query()->where('article_id', $article->id)->count());
    }

    public function test_api_registration_creates_reader_and_token_without_role_escalation(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Новый читатель',
            'email' => 'new-reader-rest@example.test',
            'password' => 'ValidPassword2026!',
            'password_confirmation' => 'ValidPassword2026!',
            'role_id' => 999999,
            'is_moderator' => true,
        ])->assertCreated()
            ->assertJsonPath('data.role', Role::READER)
            ->assertJsonPath('token_type', 'Bearer')
            ->assertDontSee('password');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));

        $token = $response->json('token');
        $this->assertIsString($token);
        $this->assertDatabaseHas('users', ['email' => 'new-reader-rest@example.test']);

        $this->withToken($token)->getJson('/api/user')
            ->assertOk()->assertJsonPath('data.role', Role::READER);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_invalid_registration_is_json_422_and_does_not_create_user(): void
    {
        $this->postJson('/api/register', [
            'name' => 'A',
            'email' => 'not-an-email',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'password']);

        $this->assertSame(0, User::count());
    }

    public function test_login_errors_are_json_and_password_never_returns(): void
    {
        $this->person(Role::READER, 'login-rest@example.test');

        $this->postJson('/api/login', [
            'email' => 'login-rest@example.test',
            'password' => 'incorrect',
        ])->assertUnauthorized()
            ->assertJsonPath('message', 'Неверная почта или пароль.');

        $this->postJson('/api/login', [
            'email' => 'invalid-email',
            'password' => '',
        ])->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);

        $response = $this->postJson('/api/login', [
            'email' => 'login-rest@example.test',
            'password' => 'ValidPassword2026!',
        ])->assertOk()->assertJsonPath('data.role', Role::READER);

        $this->assertIsString($response->json('token'));
        $this->assertArrayNotHasKey('password', $response->json('data'));
        $this->assertArrayNotHasKey('remember_token', $response->json('data'));
    }

    public function test_unauthenticated_api_mutations_return_401_json_not_html_redirect(): void
    {
        $this->getJson('/api/user')->assertUnauthorized()->assertJsonPath('message', 'Unauthenticated.');
        $this->getJson('/api/articles-management')->assertUnauthorized();
        $this->postJson('/api/articles', $this->articleData())->assertUnauthorized();
        $this->putJson('/api/articles/1', $this->articleData())->assertUnauthorized();
        $this->deleteJson('/api/articles/1')->assertUnauthorized();
        $this->postJson('/api/logout')->assertUnauthorized();
    }

    public function test_reader_bearer_token_cannot_manage_or_change_articles(): void
    {
        $reader = $this->person(Role::READER, 'reader-deny-rest@example.test');
        $article = Article::factory()->create();
        $this->withToken($reader->createToken('Test')->plainTextToken);

        $this->getJson('/api/articles-management')->assertForbidden();
        $this->postJson('/api/articles', $this->articleData())->assertForbidden();
        $this->putJson('/api/articles/'.$article->id, $this->articleData())->assertForbidden();
        $this->patchJson('/api/articles/'.$article->id, ['title' => 'Новая статья'])->assertForbidden();
        $this->deleteJson('/api/articles/'.$article->id)->assertForbidden();

        $this->assertDatabaseHas('articles', ['id' => $article->id, 'title' => $article->title]);
    }

    public function test_moderator_can_create_json_and_api_keeps_notifications_mail_and_cache(): void
    {
        Notification::fake();
        $reader = $this->person(Role::READER, 'news-notice-rest@example.test');
        $token = $this->moderatorToken();
        $this->get('/news')->assertOk();
        $this->assertNotNull(Cache::get('news.index.version'));

        $response = $this->withToken($token)->postJson('/api/articles', $this->articleData())
            ->assertCreated()
            ->assertJsonPath('data.title', 'Тестовая статья для API')
            ->assertJsonPath('data.is_published', true)
            ->assertJsonPath('message', 'Статья сохранена. Задание на рассылку создано.');

        $id = $response->json('data.id');
        $this->assertSame(route('api.articles.show', $id), $response->headers->get('Location'));
        $this->assertNull(Cache::get('news.index.version'));
        $this->assertDatabaseHas('articles', ['id' => $id]);
        Notification::assertSentTo($reader, NewArticleNotification::class);
        Mail::assertSent(ArticleCreatedMail::class, 1);
        Event::assertDispatched(NewArticleEvent::class);
    }

    public function test_moderator_can_manage_drafts_patch_publish_and_delete_through_rest(): void
    {
        Notification::fake();
        $reader = $this->person(Role::READER, 'draft-reader-rest@example.test');
        $this->withToken($this->moderatorToken('draft-mod-rest@example.test'));

        $creation = $this->postJson('/api/articles', $this->articleData('Черновик для REST', false))
            ->assertCreated()->assertJsonPath('data.is_published', false);
        $id = $creation->json('data.id');

        $this->getJson('/api/articles-management')->assertOk()
            ->assertJsonFragment(['title' => 'Черновик для REST']);

        // Модератор может смотреть черновик (правило Gate::before).
        $this->getJson('/api/articles/'.$id)->assertOk();
        // Публичный список при этом черновик не раскрывает.
        $this->getJson('/api/articles')->assertDontSee('Черновик для REST');
        Notification::assertNothingSent();
        Event::assertNotDispatched(NewArticleEvent::class);

        $this->patchJson('/api/articles/'.$id, [
            'title' => 'Опубликованная через PATCH статья',
            'is_published' => true,
        ])->assertOk()
            ->assertJsonPath('data.title', 'Опубликованная через PATCH статья')
            ->assertJsonPath('data.is_published', true);

        Notification::assertSentTo($reader, NewArticleNotification::class);
        Event::assertDispatchedTimes(NewArticleEvent::class, 1);
        $this->getJson('/api/articles/'.$id)->assertOk();

        $this->patchJson('/api/articles/'.$id, [
            'summary' => 'Достаточно длинное описание после изменения.',
        ])->assertOk();
        Event::assertDispatchedTimes(NewArticleEvent::class, 1);

        $this->putJson('/api/articles/'.$id, $this->articleData('Обновлено через PUT', true))
            ->assertOk()->assertJsonPath('data.title', 'Обновлено через PUT');

        $this->deleteJson('/api/articles/'.$id)->assertNoContent();
        $this->getJson('/api/articles/'.$id)->assertNotFound();
    }

    public function test_api_crud_validates_json_and_does_not_save_invalid_article(): void
    {
        $this->withToken($this->moderatorToken('validation-mod-rest@example.test'));
        $this->postJson('/api/articles', [
            'title' => '',
            'summary' => 'short',
            'content' => 'short',
            'published_at' => 'wrong date',
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'title', 'summary', 'content', 'published_at',
        ]);

        $this->assertSame(0, Article::count());
        $article = Article::factory()->create();

        $this->putJson('/api/articles/'.$article->id, ['title' => 'Есть заголовок'])
            ->assertUnprocessable()->assertJsonValidationErrors([
                'summary', 'content', 'published_at',
            ]);
        $this->assertDatabaseHas('articles', ['id' => $article->id, 'title' => $article->title]);
    }

    public function test_logout_revokes_only_current_bearer_token(): void
    {
        $user = $this->person(Role::READER, 'logout-rest@example.test');
        $first = $user->createToken('Device A')->plainTextToken;
        $second = $user->createToken('Device B')->plainTextToken;

        $this->withToken($first)->getJson('/api/user')->assertOk();
        $this->postJson('/api/logout')->assertOk()
            ->assertJsonPath('message', 'Выход выполнен. Текущий токен отозван.');

        // Убедимся, что доступ аннулирован именно в таблице Sanctum.
        $this->assertSame(1, $user->tokens()->count());
        $this->assertFalse($user->tokens()->where('token', hash('sha256', explode('|', $first, 2)[1]))->exists());

        // PHP тестовый клиент переиспользует auth guard между запросами.
        // Обнуляем guard, как при следующем реальном HTTP-запросе Postman.
        $this->app['auth']->forgetGuards();
        $this->withToken($first)->getJson('/api/user')->assertUnauthorized();

        $this->app['auth']->forgetGuards();
        $this->withToken($second)->getJson('/api/user')->assertOk();
    }

    public function test_original_blade_site_remains_available_and_api_routes_exist(): void
    {
        $this->get('/')->assertOk()->assertSee('html', false);
        $this->get('/news')->assertOk();
        $this->get('/about')->assertOk();
        $this->get('/login')->assertOk();

        $this->assertSame(0, \Illuminate\Support\Facades\Artisan::call(
            'route:list',
            ['--path' => 'api', '--json' => true]
        ));
        $routeList = json_decode(\Illuminate\Support\Facades\Artisan::output(), true);
        $this->assertIsArray($routeList);
        $this->assertContains('api/articles', array_column($routeList, 'uri'));
        $this->assertContains('api/login', array_column($routeList, 'uri'));
        $this->assertContains('api/register', array_column($routeList, 'uri'));
    }
}
