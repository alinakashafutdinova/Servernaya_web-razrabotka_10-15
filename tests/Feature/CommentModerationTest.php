<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Comment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CommentModerationTest extends TestCase
{
    use RefreshDatabase;

    private function actor(string $role, string $email): User
    {
        $roleModel = Role::firstOrCreate(['slug' => $role], ['name' => $role === 'moderator' ? 'Модератор' : 'Читатель']);
        return User::create([
            'name' => $role === 'moderator' ? 'Модератор' : 'Читатель',
            'email' => $email,
            'role_id' => $roleModel->id,
            'password' => Hash::make('PasswordStrong2026!'),
        ]);
    }

    private function comment(Article $article, User $user, string $body, bool $approved = false): Comment
    {
        $comment = $article->comments()->create(['user_id' => $user->id, 'body' => $body]);
        if ($approved) {
            $comment->is_approved = true;
            $comment->save();
        }
        return $comment->fresh();
    }

    public function test_migration_adds_approval_field_and_new_comment_is_unapproved(): void
    {
        $this->assertTrue(Schema::hasColumn('comments', 'is_approved'));
        $reader = $this->actor('reader', 'r1@example.test');
        $article = Article::factory()->create();
        $comment = $this->comment($article, $reader, 'Новый комментарий');
        $this->assertFalse($comment->is_approved);
        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'is_approved' => 0]);
    }

    public function test_reader_submits_pending_comment_and_gets_moderation_message(): void
    {
        $reader = $this->actor('reader', 'r2@example.test');
        $article = Article::factory()->create();
        $this->actingAs($reader)->post(route('comments.store', $article), ['body' => 'Очень полезная публикация'])
            ->assertRedirect(route('news.show', $article))
            ->assertSessionHas('status', 'Комментарий отправлен и ожидает модерации.');
        $this->assertDatabaseHas('comments', ['article_id' => $article->id, 'body' => 'Очень полезная публикация', 'is_approved' => 0]);
        $this->get(route('news.show', $article))->assertOk()
            ->assertSeeText('ожидает модерации')
            ->assertDontSeeText('Очень полезная публикация');
    }

    public function test_article_page_displays_only_approved_comments_even_to_moderator(): void
    {
        $reader = $this->actor('reader', 'r3@example.test');
        $moderator = $this->actor('moderator', 'm3@example.test');
        $article = Article::factory()->create();
        $this->comment($article, $reader, 'В ОЧЕРЕДИ — НЕ ПОКАЗЫВАТЬ');
        $this->comment($article, $reader, '<script>опасность</script> ОДОБРЕНО', true);
        $this->get(route('news.show', $article))->assertOk()
            ->assertDontSeeText('В ОЧЕРЕДИ — НЕ ПОКАЗЫВАТЬ')
            ->assertSee('&lt;script&gt;опасность&lt;/script&gt;', false)
            ->assertDontSee('<script>опасность</script>', false)
            ->assertSee('comments-count', false);
        $this->actingAs($moderator)->get(route('news.show', $article))->assertOk()
            ->assertDontSeeText('В ОЧЕРЕДИ — НЕ ПОКАЗЫВАТЬ');
    }

    public function test_moderation_list_contains_only_pending_comments(): void
    {
        $moderator = $this->actor('moderator', 'm4@example.test');
        $reader = $this->actor('reader', 'r4@example.test');
        $article = Article::factory()->create();
        $this->comment($article, $reader, 'Ожидающий комментарий');
        $this->comment($article, $reader, 'Уже опубликованный комментарий', true);
        $this->actingAs($moderator)->get(route('comments.moderation'))->assertOk()
            ->assertSeeText('Ожидающий комментарий')
            ->assertDontSeeText('Уже опубликованный комментарий')
            ->assertSeeText($article->title)
            ->assertSee('name="_token"', false)
            ->assertSee('name="_method" value="PATCH"', false)
            ->assertSee('name="_method" value="DELETE"', false);
    }

    public function test_only_moderator_sees_moderation_link_in_navigation(): void
    {
        $reader = $this->actor('reader', 'r5@example.test');
        $moderator = $this->actor('moderator', 'm5@example.test');
        $this->get('/')->assertDontSee('href="'.route('comments.moderation').'"', false);
        $this->actingAs($reader)->get('/')->assertDontSee('href="'.route('comments.moderation').'"', false);
        $this->actingAs($moderator)->get('/')->assertSee('href="'.route('comments.moderation').'"', false);
    }

    public function test_guest_and_reader_cannot_moderate_or_open_queue(): void
    {
        $reader = $this->actor('reader', 'r6@example.test');
        $article = Article::factory()->create();
        $comment = $this->comment($article, $reader, 'На рассмотрении');
        $this->get(route('comments.moderation'))->assertRedirect(route('login'));
        $this->patch(route('comments.approve', $comment))->assertRedirect(route('login'));
        $this->delete(route('comments.reject', $comment))->assertRedirect(route('login'));
        $this->actingAs($reader)->get(route('comments.moderation'))->assertForbidden()
            ->assertSeeText('Управлять комментариями может только модератор.');
        $this->patch(route('comments.approve', $comment))->assertForbidden();
        $this->delete(route('comments.reject', $comment))->assertForbidden();
        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'is_approved' => 0]);
    }

    public function test_moderator_approves_comment_then_it_appears_under_article(): void
    {
        $reader = $this->actor('reader', 'r7@example.test');
        $moderator = $this->actor('moderator', 'm7@example.test');
        $article = Article::factory()->create();
        $comment = $this->comment($article, $reader, 'Комментарий к публикации');
        $this->actingAs($moderator)->patch(route('comments.approve', $comment))
            ->assertRedirect(route('comments.moderation'))
            ->assertSessionHas('status', 'Комментарий опубликован. Автор получил уведомление в своём профиле.');
        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'is_approved' => 1]);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $reader->id, 'notifiable_type' => User::class]);
        $this->actingAs($reader)->get(route('account'))->assertOk()->assertSeeText('Комментарий опубликован');
        $this->get(route('news.show', $article))->assertSeeText('Комментарий к публикации');
        $this->get(route('comments.moderation'))->assertDontSeeText('Комментарий к публикации');
    }

    public function test_moderator_rejects_comment_then_it_is_deleted(): void
    {
        $reader = $this->actor('reader', 'r8@example.test');
        $moderator = $this->actor('moderator', 'm8@example.test');
        $article = Article::factory()->create();
        $comment = $this->comment($article, $reader, 'Отклоняемый текст');
        $this->actingAs($moderator)->delete(route('comments.reject', $comment))
            ->assertRedirect(route('comments.moderation'))
            ->assertSessionHas('status', 'Комментарий отклонён и удалён. Автор получил уведомление в своём профиле.');
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $reader->id, 'notifiable_type' => User::class]);
        $this->actingAs($reader)->get(route('account'))->assertOk()->assertSeeText('Комментарий отклонён');
        $this->get(route('news.show', $article))->assertDontSeeText('Отклоняемый текст');
    }

    public function test_approved_comments_cannot_be_processed_again(): void
    {
        $reader = $this->actor('reader', 'r9@example.test');
        $moderator = $this->actor('moderator', 'm9@example.test');
        $article = Article::factory()->create();
        $comment = $this->comment($article, $reader, 'Опубликованный', true);
        $this->actingAs($moderator)->patch(route('comments.approve', $comment))->assertNotFound();
        $this->delete(route('comments.reject', $comment))->assertNotFound();
        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'is_approved' => 1]);
        $this->patch('/comments/999999/approve')->assertNotFound();
    }

    public function test_moderation_queue_paginates_and_empty_message_is_available(): void
    {
        $reader = $this->actor('reader', 'r10@example.test');
        $moderator = $this->actor('moderator', 'm10@example.test');
        $article = Article::factory()->create();
        $this->actingAs($moderator)->get(route('comments.moderation'))->assertOk()
            ->assertSeeText('Новых комментариев пока нет');
        for ($i = 0; $i < 10; $i++) {
            $this->comment($article, $reader, 'Сообщение для очереди №'.($i + 1));
        }
        $this->get(route('comments.moderation'))->assertOk()->assertSeeText('На проверке: 10')
            ->assertSeeText('Сообщение для очереди №1')
            ->assertDontSeeText('Сообщение для очереди №10');
        $this->get(route('comments.moderation', ['page' => 2]))->assertOk()
            ->assertSeeText('Сообщение для очереди №10');
    }

    public function test_moderation_actions_require_sanctum_and_correct_http_methods(): void
    {
        foreach (['comments.moderation', 'comments.approve', 'comments.reject'] as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route);
            $this->assertContains('auth:sanctum', $route->gatherMiddleware());
        }
        $reader = $this->actor('reader', 'r11@example.test');
        $moderator = $this->actor('moderator', 'm11@example.test');
        $article = Article::factory()->create();
        $comment = $this->comment($article, $reader, 'Ожидаю');
        $this->actingAs($moderator)->get(route('comments.approve', $comment))->assertStatus(405);
        $this->get(route('comments.reject', $comment))->assertStatus(405);
    }

    public function test_invalid_comment_is_not_saved_or_published(): void
    {
        $reader = $this->actor('reader', 'r12@example.test');
        $article = Article::factory()->create();
        $this->actingAs($reader)->from(route('news.show', $article))
            ->post(route('comments.store', $article), ['body' => 'a'])
            ->assertSessionHasErrors('body');
        $this->assertDatabaseCount('comments', 0);
    }
}
