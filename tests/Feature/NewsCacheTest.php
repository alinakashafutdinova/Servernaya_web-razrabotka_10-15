<?php

namespace Tests\Feature;

use App\Events\NewArticleEvent;
use App\Models\Article;
use App\Models\Comment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NewsCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // phpunit.xml использует изолированный array-кеш для прочих тестов.
        // В ЛР №13 тестируем именно реальное хранилище database.
        config()->set('cache.default', 'database');
        Mail::fake();
        Event::fake([NewArticleEvent::class]);
    }

    private function actor(string $slug, string $email): User
    {
        $role = Role::firstOrCreate(
            ['slug' => $slug],
            ['name' => $slug === Role::MODERATOR ? 'Модератор' : 'Читатель']
        );

        return User::create([
            'role_id' => $role->id,
            'name' => $slug === Role::MODERATOR ? 'Модератор' : 'Читатель',
            'email' => $email,
            'password' => Hash::make('VeryStrongPassword2026!'),
        ]);
    }

    private function validArticle(string $title, string $published = '1'): array
    {
        return [
            'title' => $title,
            'summary' => 'Краткое описание для проверки кеширования новостей.',
            'content' => 'Подробный текст статьи и тестирование хранения данных.',
            'published_at' => '2026-11-19',
            'is_published' => $published,
        ];
    }

    public function test_migration_creates_tables_and_real_database_cache_store_works(): void
    {
        $this->assertTrue(Schema::hasTable('cache'));
        $this->assertTrue(Schema::hasTable('cache_locks'));
        $this->assertSame('database', config('cache.default'));

        Cache::put('lab13.db-check', ['success' => true], 120);
        $this->assertSame(['success' => true], Cache::get('lab13.db-check'));
        $this->assertTrue(DB::table('cache')->where('key', 'like', '%lab13.db-check')->exists());
    }

    public function test_paginated_index_uses_separate_cached_pages_and_forget_invalidates_all(): void
    {
        Article::factory()->count(13)->create([
            'published_at' => '2026-11-18 09:00:00',
        ]);
        $first = $this->get(route('news.index'))->assertOk();
        $second = $this->get(route('news.index', ['page' => 2]))->assertOk();

        $version = Cache::get('news.index.version');
        $this->assertIsString($version);
        $this->assertCount(6, $first->viewData('articles')->items());
        $oldSecondIds = array_map(fn (Article $article): int => $article->id, $second->viewData('articles')->items());
        $this->assertCount(6, $oldSecondIds);
        $this->assertNotNull(Cache::get("news.index.{$version}.page.1"));
        $this->assertNotNull(Cache::get("news.index.{$version}.page.2"));

        Article::factory()->create([
            'title' => 'Новая статья напрямую в БД',
            'published_at' => '2026-11-19 13:00:00',
        ]);
        // Обычное чтение использует кеш, до штатного вызова store.
        $this->get(route('news.index'))->assertDontSeeText('Новая статья напрямую в БД');

        $this->actingAs($this->actor(Role::MODERATOR, 'cache-moderator@example.test'))
            ->post(route('news.store'), $this->validArticle('Статья через контроллер'))
            ->assertRedirect(route('news.manage'));

        // Одна операция forget сбрасывает всю версию списка, не только page=1.
        $this->assertNull(Cache::get('news.index.version'));
        $newFirst = $this->get(route('news.index'))->assertOk()
            ->assertSeeText('Статья через контроллер');
        $this->assertSame(15, $newFirst->viewData('articles')->total());

        $newSecond = $this->get(route('news.index', ['page' => 2]))->assertOk();
        $newSecondIds = array_map(fn (Article $article): int => $article->id, $newSecond->viewData('articles')->items());
        $this->assertNotSame($oldSecondIds, $newSecondIds);
        $this->assertNotSame($version, Cache::get('news.index.version'));
    }

    public function test_article_and_approved_comments_are_cached_forever_as_safe_arrays(): void
    {
        $reader = $this->actor(Role::READER, 'cached-reader@example.test');
        $article = Article::factory()->create(['title' => 'Статья с кешированными комментариями']);
        $comment = $article->comments()->create([
            'body' => 'Одобренный комментарий',
            'user_id' => $reader->id,
        ]);
        $comment->is_approved = true;
        $comment->save();

        $this->get(route('news.show', $article))->assertOk()
            ->assertSeeText('Одобренный комментарий')
            ->assertSeeText($reader->name);

        $payload = Cache::get("news.show.{$article->id}");
        $this->assertIsArray($payload);
        $this->assertSame($article->id, $payload['article']['id']);
        $this->assertSame('Одобренный комментарий', $payload['comments'][0]['attributes']['body']);
        $this->assertSame(['id' => $reader->id, 'name' => $reader->name], $payload['comments'][0]['user']);
        $this->assertStringNotContainsString('password', json_encode($payload));

        DB::table('comments')->where('id', $comment->id)->update(['body' => 'Текст напрямую в таблице']);
        $this->get(route('news.show', $article))->assertSeeText('Одобренный комментарий')
            ->assertDontSeeText('Текст напрямую в таблице');

        Cache::forget("news.show.{$article->id}");
        $this->get(route('news.show', $article))->assertSeeText('Текст напрямую в таблице');
    }

    public function test_approving_comment_removes_cached_show_and_displays_new_text(): void
    {
        $reader = $this->actor(Role::READER, 'approval-reader@example.test');
        $moderator = $this->actor(Role::MODERATOR, 'approval-mod@example.test');
        $article = Article::factory()->create();
        $comment = $article->comments()->create([
            'body' => 'Текст после одобрения комментария',
            'user_id' => $reader->id,
        ]);

        $this->get(route('news.show', $article))->assertOk()
            ->assertDontSeeText('Текст после одобрения комментария');
        $this->assertNotNull(Cache::get("news.show.{$article->id}"));

        $this->actingAs($moderator)->patch(route('comments.approve', $comment))
            ->assertRedirect(route('comments.moderation'));

        $this->assertNull(Cache::get("news.show.{$article->id}"));
        $this->get(route('news.show', $article))->assertOk()
            ->assertSeeText('Текст после одобрения комментария');
    }

    public function test_direct_eloquent_comment_approval_also_invalidates_cached_page(): void
    {
        $reader = $this->actor(Role::READER, 'eloquent-reader@example.test');
        $article = Article::factory()->create();
        $comment = $article->comments()->create([
            'body' => 'Текст одобрен вне контроллера',
            'user_id' => $reader->id,
        ]);

        $this->get(route('news.show', $article))->assertOk();
        $this->assertNotNull(Cache::get("news.show.{$article->id}"));

        // Некоторые старые тесты одобряют через модель, минуя маршрут.
        $comment->is_approved = true;
        $comment->save();

        $this->assertNull(Cache::get("news.show.{$article->id}"));
        $this->get(route('news.show', $article))->assertSeeText('Текст одобрен вне контроллера');
    }

    public function test_rejecting_comment_does_not_leave_cached_page(): void
    {
        $reader = $this->actor(Role::READER, 'reject-reader@example.test');
        $moderator = $this->actor(Role::MODERATOR, 'reject-mod@example.test');
        $article = Article::factory()->create();
        $comment = $article->comments()->create([
            'body' => 'Отказанный текст в комментарии',
            'user_id' => $reader->id,
        ]);

        $this->get(route('news.show', $article))->assertOk();
        $this->actingAs($moderator)->delete(route('comments.reject', $comment))->assertRedirect();
        $this->assertNull(Cache::get("news.show.{$article->id}"));
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_update_flushes_database_cache_and_shows_updated_article(): void
    {
        $moderator = $this->actor(Role::MODERATOR, 'update-mod@example.test');
        $article = Article::factory()->create(['title' => 'Старая версия']);
        $this->get(route('news.index'))->assertOk();
        $this->get(route('news.show', $article))->assertSeeText('Старая версия');
        Cache::put('lab13.test-marker', 'present', 600);

        $this->actingAs($moderator)->put(route('news.update', $article), $this->validArticle('Новая версия'))
            ->assertRedirect(route('news.manage'));

        $this->assertNull(Cache::get('lab13.test-marker'));
        $this->assertNull(Cache::get('news.index.version'));
        $this->assertNull(Cache::get("news.show.{$article->id}"));

        $this->get(route('news.show', $article))->assertOk()
            ->assertSeeText('Новая версия')->assertDontSeeText('Старая версия');
    }

    public function test_destroy_flushes_database_cache_and_removed_article_returns_404(): void
    {
        $moderator = $this->actor(Role::MODERATOR, 'delete-mod@example.test');
        $article = Article::factory()->create();
        $this->get(route('news.index'))->assertOk();
        $this->get(route('news.show', $article))->assertOk();
        Cache::put('lab13.other-marker', 'present', 600);

        $this->actingAs($moderator)->delete(route('news.destroy', $article))
            ->assertRedirect(route('news.manage'));

        $this->assertNull(Cache::get('lab13.other-marker'));
        $this->assertNull(Cache::get("news.show.{$article->id}"));
        $this->get(route('news.show', $article))->assertNotFound();
    }

    public function test_cache_does_not_reveal_unpublished_article_to_reader(): void
    {
        $moderator = $this->actor(Role::MODERATOR, 'draft-mod@example.test');
        $reader = $this->actor(Role::READER, 'draft-reader@example.test');
        $article = Article::factory()->unpublished()->create(['title' => 'Скрытая статья']);

        $this->actingAs($moderator)->get(route('news.show', $article))->assertOk()
            ->assertSeeText('Скрытая статья');
        $this->assertNotNull(Cache::get("news.show.{$article->id}"));

        $this->actingAs($reader)->get(route('news.show', $article))->assertNotFound();
        $this->get(route('news.index'))->assertDontSeeText('Скрытая статья');
    }
}
