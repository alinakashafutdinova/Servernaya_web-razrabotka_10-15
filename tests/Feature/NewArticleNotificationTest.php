<?php

namespace Tests\Feature;

use App\Events\NewArticleEvent;
use App\Models\Article;
use App\Models\Role;
use App\Models\User;
use App\Notifications\CommentModeratedNotification;
use App\Notifications\NewArticleNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NewArticleNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('queue.default', 'sync');
        Mail::fake();
        Event::fake([NewArticleEvent::class]);
    }

    private function actor(string $role, string $email): User
    {
        $roleRecord = Role::firstOrCreate(
            ['slug' => $role],
            ['name' => $role === Role::MODERATOR ? 'Модератор' : 'Читатель']
        );

        return User::create([
            'name' => $role === Role::MODERATOR ? 'Модератор' : 'Читатель',
            'role_id' => $roleRecord->id,
            'email' => $email,
            'password' => Hash::make('StrongPassword12!'),
        ]);
    }

    private function articleData(string $published = '1'): array
    {
        return [
            'title' => 'Статья для лабораторной №12',
            'summary' => 'Уведомления для читателей в базе данных.',
            'content' => 'Текст новости проверяет работу уведомлений и личного кабинета.',
            'published_at' => '2026-11-19',
            'is_published' => $published,
        ];
    }

    public function test_existing_table_and_database_channel_contract(): void
    {
        $this->assertTrue(Schema::hasTable('notifications'));
        $notification = new NewArticleNotification(17, 'Статья номер 17');
        $this->assertSame(['database'], $notification->via(new \stdClass()));
        $this->assertSame([
            'type' => 'new_article',
            'article_id' => 17,
            'article_title' => 'Статья номер 17',
            'message' => 'Опубликована новая статья',
        ], $notification->toDatabase(new \stdClass()));
    }

    public function test_only_registered_readers_get_new_article_notification(): void
    {
        Notification::fake();
        $moderator = $this->actor(Role::MODERATOR, 'moderator12@example.test');
        $otherModerator = $this->actor(Role::MODERATOR, 'moderator2-12@example.test');
        $reader1 = $this->actor(Role::READER, 'reader1-12@example.test');
        $reader2 = $this->actor(Role::READER, 'reader2-12@example.test');

        $this->actingAs($moderator)->post(route('news.store'), $this->articleData())
            ->assertRedirect(route('news.manage'));

        Notification::assertSentTo($reader1, NewArticleNotification::class);
        Notification::assertSentTo($reader2, NewArticleNotification::class);
        Notification::assertNotSentTo($moderator, NewArticleNotification::class);
        Notification::assertNotSentTo($otherModerator, NewArticleNotification::class);
        Notification::assertSentTimes(NewArticleNotification::class, 2);
    }

    public function test_drafts_are_not_notified_and_first_publication_notifies_once(): void
    {
        Notification::fake();
        $moderator = $this->actor(Role::MODERATOR, 'draftmoderator12@example.test');
        $reader = $this->actor(Role::READER, 'draftreader12@example.test');
        $this->actingAs($moderator);

        $this->post(route('news.store'), $this->articleData('0'))->assertRedirect();
        Notification::assertNothingSent();

        $article = Article::firstOrFail();
        $this->put(route('news.update', $article), $this->articleData('1'))->assertRedirect();
        Notification::assertSentTo($reader, NewArticleNotification::class);
        Notification::assertSentTimes(NewArticleNotification::class, 1);

        $this->put(route('news.update', $article), $this->articleData('1'))->assertRedirect();
        Notification::assertSentTimes(NewArticleNotification::class, 1);
    }

    public function test_database_notifications_persist_for_reader_not_logged_in(): void
    {
        $moderator = $this->actor(Role::MODERATOR, 'storedmod12@example.test');
        $reader = $this->actor(Role::READER, 'storedreader12@example.test');

        $this->actingAs($moderator)->post(route('news.store'), $this->articleData())
            ->assertRedirect(route('news.manage'));

        $this->assertDatabaseCount('notifications', 1);
        $entry = $reader->unreadNotifications()->firstOrFail();
        $this->assertSame(NewArticleNotification::class, $entry->type);
        $this->assertSame('Статья для лабораторной №12', $entry->data['article_title']);
        $this->assertNull($entry->read_at);

        $this->actingAs($reader)->get(route('home'))
            ->assertOk()
            ->assertSeeText('Уведомления')
            ->assertSeeText('Статья для лабораторной №12')
            ->assertSee('readers-notifications__menu', false)
            ->assertSee(route('account.notifications.open', $entry->id), false)
            ->assertSee('name="_token"', false);
    }

    public function test_clicking_own_notification_opens_article_and_marks_only_that_one_read(): void
    {
        $reader = $this->actor(Role::READER, 'clickreader12@example.test');
        $article = Article::factory()->create();
        $reader->notify(new NewArticleNotification($article->id, $article->title));
        $reader->notify(new NewArticleNotification($article->id, 'Повторная запись'));

        $first = $reader->unreadNotifications()->orderBy('created_at')->firstOrFail();
        $second = $reader->unreadNotifications()->whereKeyNot($first->id)->firstOrFail();

        $this->actingAs($reader)
            ->post(route('account.notifications.open', $first->id))
            ->assertRedirect(route('news.show', $article));

        $this->assertNotNull($first->fresh()->read_at);
        $this->assertNull($second->fresh()->read_at);
        $this->assertSame(1, $reader->unreadNotifications()->count());
        $this->get(route('account'))->assertOk()->assertSeeText('Новая публикация');
    }

    public function test_other_readers_cannot_open_or_mark_notification(): void
    {
        $owner = $this->actor(Role::READER, 'owner12@example.test');
        $stranger = $this->actor(Role::READER, 'stranger12@example.test');
        $article = Article::factory()->create();
        $owner->notify(new NewArticleNotification($article->id, $article->title));

        $record = $owner->unreadNotifications()->firstOrFail();
        $this->actingAs($stranger)->post(route('account.notifications.open', $record->id))
            ->assertNotFound();
        $this->assertNull($record->fresh()->read_at);
        $this->get(route('home'))->assertDontSeeText($article->title);
    }

    public function test_comment_moderation_notifications_remain_separate_and_unaffected(): void
    {
        $reader = $this->actor(Role::READER, 'moderationreader12@example.test');
        $article = Article::factory()->create();
        $reader->notify(new CommentModeratedNotification(true, $article->id, $article->title, 'Мой комментарий'));

        $record = $reader->unreadNotifications()->firstOrFail();

        $this->actingAs($reader)->post(route('account.notifications.open', $record->id))
            ->assertNotFound();
        $this->assertNull($record->fresh()->read_at);
        $this->get(route('account'))->assertOk()->assertSeeText('Комментарий опубликован');
    }

    public function test_removed_or_unpublished_article_redirects_safely_and_marks_notification_read(): void
    {
        $reader = $this->actor(Role::READER, 'missingarticle12@example.test');
        $article = Article::factory()->unpublished()->create();
        $reader->notify(new NewArticleNotification($article->id, $article->title));
        $record = $reader->unreadNotifications()->firstOrFail();

        $this->actingAs($reader)->post(route('account.notifications.open', $record->id))
            ->assertRedirect(route('news.index'))
            ->assertSessionHas('status', 'Эта статья больше недоступна.');
        $this->assertNotNull($record->fresh()->read_at);
    }
}
