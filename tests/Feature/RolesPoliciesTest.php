<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Comment;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RolesPoliciesTest extends TestCase
{
    use RefreshDatabase;

    private function actor(string $role, string $email): User
    {
        $roleModel = Role::firstOrCreate(['slug' => $role], ['name' => $role === 'moderator' ? 'Модератор' : 'Читатель']);
        return User::create([
            'name' => 'Проверка',
            'email' => $email,
            'role_id' => $roleModel->id,
            'password' => Hash::make('SafePassword123!'),
        ]);
    }

    private function articleData(): array
    {
        return [
            'title' => 'Новая новость для проверки ролей',
            'summary' => 'Проверка создания статьи в системе ролей',
            'content' => 'Подробное содержание новой статьи для проверки прав доступа.',
            'published_at' => '2026-11-19',
            'is_published' => '1',
        ];
    }

    public function test_roles_migrations_and_relationships_exist(): void
    {
        $this->assertTrue(Schema::hasTable('roles'));
        $this->assertTrue(Schema::hasTable('comments'));
        $this->assertTrue(Schema::hasTable('notifications'));
        $this->assertTrue(Schema::hasColumn('users', 'role_id'));
        $this->seed(RoleSeeder::class);
        $this->assertSame(2, Role::count());
        $reader = $this->actor('reader', 'reader1@example.test');
        $this->assertSame('reader', $reader->role->slug);
        $this->assertSame($reader->id, Role::where('slug', 'reader')->firstOrFail()->users()->firstOrFail()->id);
    }

    public function test_default_seeder_creates_moderator_and_roles(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->assertSame(2, Role::count());
        $moderator = User::whereHas('role', fn ($q) => $q->where('slug', 'moderator'))->first();
        $this->assertNotNull($moderator);
        $this->assertTrue($moderator->isModerator());
        $this->assertSame(12, Article::count());
    }

    public function test_registration_creates_reader_not_moderator(): void
    {
        $this->post('/register', [
            'name' => 'Новый читатель',
            'email' => 'new-reader@example.test',
            'password' => 'SafePassword123!',
            'password_confirmation' => 'SafePassword123!',
            'role_id' => 999,
        ])->assertRedirect(route('login'));
        $this->assertSame('reader', User::where('email', 'new-reader@example.test')->firstOrFail()->role->slug);
    }

    public function test_reader_can_read_and_comment_but_cannot_manage_articles(): void
    {
        $reader = $this->actor('reader', 'reader2@example.test');
        $article = Article::factory()->create();
        $this->actingAs($reader);
        $this->get('/news')->assertOk();
        $this->get('/news/'.$article->id)->assertOk()->assertSee('name="body"', false);
        $this->get('/management')->assertForbidden()->assertSeeText('Управление статьями доступно только модератору.');
        $this->get('/news/create')->assertForbidden();
        $this->get('/news/'.$article->id.'/edit')->assertForbidden();
        $this->post('/news', $this->articleData())->assertForbidden();
        $this->put('/news/'.$article->id, $this->articleData())->assertForbidden();
        $this->delete('/news/'.$article->id)->assertForbidden();
        $this->assertSame(1, Article::count());
        $this->assertFalse(Gate::forUser($reader)->allows('manage-comments'));
    }

    public function test_gate_before_gives_moderator_all_article_and_comment_rights(): void
    {
        $moderator = $this->actor('moderator', 'moderator1@example.test');
        $article = Article::factory()->unpublished()->create();
        $this->assertTrue(Gate::forUser($moderator)->allows('manage-comments'));
        $this->assertTrue(Gate::forUser($moderator)->allows('update', $article));
        $this->actingAs($moderator);
        $this->get('/management')->assertOk();
        $this->get('/news/create')->assertOk();
        $this->get('/news/'.$article->id)->assertOk();
        $this->post('/news', $this->articleData())->assertRedirect(route('news.manage'));
        $this->put('/news/'.$article->id, $this->articleData())->assertRedirect(route('news.manage'));
        $this->assertDatabaseHas('articles', ['id' => $article->id, 'title' => $this->articleData()['title']]);
    }

    public function test_navigation_shows_create_news_only_to_moderator(): void
    {
        $reader = $this->actor('reader', 'reader3@example.test');
        $moderator = $this->actor('moderator', 'moderator3@example.test');
        $this->get('/')->assertDontSeeText('Создать новость');
        $this->actingAs($reader)->get('/')->assertDontSeeText('Создать новость');
        $this->actingAs($moderator)->get('/')->assertSeeText('Создать новость');
    }

    public function test_reader_comments_are_stored_and_html_is_escaped(): void
    {
        $reader = $this->actor('reader', 'reader4@example.test');
        $article = Article::factory()->create();
        $this->actingAs($reader)->post('/news/'.$article->id.'/comments', [
            'body' => '<script>alert(5)</script> полезная статья',
        ])->assertRedirect(route('news.show', $article));
        $this->assertDatabaseCount('comments', 1);
        $this->assertSame($reader->id, Comment::firstOrFail()->user_id);
        $this->assertFalse(Comment::firstOrFail()->is_approved);
        $this->get('/news/'.$article->id)->assertOk()
            ->assertDontSee('<script>alert(5)</script>', false)
            ->assertDontSee('&lt;script&gt;alert(5)&lt;/script&gt;', false)
            ->assertSeeText('Комментарий отправлен и ожидает модерации.');

        $comment = Comment::firstOrFail();
        $comment->is_approved = true;
        $comment->save();
        $this->get('/news/'.$article->id)->assertOk()
            ->assertDontSee('<script>alert(5)</script>', false)
            ->assertSee('&lt;script&gt;alert(5)&lt;/script&gt;', false);
    }

    public function test_reader_cannot_modify_even_own_comments(): void
    {
        $reader = $this->actor('reader', 'reader5@example.test');
        $article = Article::factory()->create();
        $comment = $article->comments()->create(['body' => 'Мой комментарий', 'user_id' => $reader->id]);
        $this->actingAs($reader);
        $this->get('/comments/'.$comment->id.'/edit')->assertNotFound();
        $this->put('/comments/'.$comment->id, ['body' => 'Изменённый текст'])->assertNotFound();
        $this->delete('/comments/'.$comment->id)->assertNotFound();
        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'body' => 'Мой комментарий']);
    }

    public function test_moderator_may_moderate_comments_without_rewriting_their_text(): void
    {
        $moderator = $this->actor('moderator', 'moderator6@example.test');
        $reader = $this->actor('reader', 'reader6@example.test');
        $article = Article::factory()->create();
        $comment = $article->comments()->create(['body' => 'Первый текст', 'user_id' => $reader->id]);
        $this->actingAs($moderator);
        $this->get('/comments/'.$comment->id.'/edit')->assertNotFound();
        $this->put('/comments/'.$comment->id, ['body' => 'Исправленный комментарий'])->assertNotFound();
        $this->delete('/comments/'.$comment->id)->assertNotFound();
        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'body' => 'Первый текст']);
        $this->patch(route('comments.approve', $comment))->assertRedirect(route('comments.moderation'));
        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'body' => 'Первый текст', 'is_approved' => 1]);
    }

    public function test_guests_and_readers_cannot_comment_on_drafts(): void
    {
        $article = Article::factory()->unpublished()->create();
        $reader = $this->actor('reader', 'reader7@example.test');
        $this->post('/news/'.$article->id.'/comments', ['body' => 'Гостевой комментарий'])->assertRedirect(route('login'));
        $this->actingAs($reader)->post('/news/'.$article->id.'/comments', ['body' => 'Комментарий читателя'])->assertNotFound();
        $this->get('/news/'.$article->id)->assertNotFound();
        $this->assertDatabaseCount('comments', 0);
    }

    public function test_comment_validation_and_csrf_field(): void
    {
        $reader = $this->actor('reader', 'reader8@example.test');
        $article = Article::factory()->create();
        $this->actingAs($reader);
        $this->get('/news/'.$article->id)->assertOk()->assertSee('name="_token"', false);
        $this->from('/news/'.$article->id)->post('/news/'.$article->id.'/comments', ['body' => 'a'])
            ->assertRedirect('/news/'.$article->id)->assertSessionHasErrors('body');
        $this->assertDatabaseCount('comments', 0);
    }

    public function test_comment_foreign_keys_cascade_when_article_deleted(): void
    {
        $reader = $this->actor('reader', 'reader9@example.test');
        $moderator = $this->actor('moderator', 'moderator9@example.test');
        $article = Article::factory()->create();
        $article->comments()->create(['body' => 'Тестовая запись', 'user_id' => $reader->id]);
        $this->actingAs($moderator)->delete('/news/'.$article->id)->assertRedirect();
        $this->assertDatabaseCount('comments', 0);
    }

    public function test_role_associations_and_moderator_guard_on_routes(): void
    {
        foreach (['comments.store', 'comments.moderation', 'comments.approve', 'comments.reject', 'account', 'account.notifications.read', 'news.manage', 'news.create', 'news.store'] as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route);
            $this->assertContains('auth:sanctum', $route->gatherMiddleware());
        }
    }
}
