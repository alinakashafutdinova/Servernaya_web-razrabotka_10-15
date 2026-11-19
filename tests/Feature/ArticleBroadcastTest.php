<?php

namespace Tests\Feature;

use App\Events\NewArticleEvent;
use App\Models\Article;
use App\Models\Role;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ArticleBroadcastTest extends TestCase
{
    use RefreshDatabase;

    private function moderator(): User
    {
        $role = Role::firstOrCreate(['slug' => Role::MODERATOR], ['name' => 'Модератор']);
        return User::create([
            'role_id' => $role->id,
            'name' => 'Проверяющий',
            'email' => 'broadcast-moderator@example.test',
            'password' => Hash::make('StrongPassword11!'),
        ]);
    }

    private function articleData(string $published): array
    {
        return [
            'title' => 'Новая статья для онлайн-подписчиков',
            'summary' => 'Краткое описание опубликованной новости на нашем сайте.',
            'content' => 'Подробная информация о новой новости и проверке широковещания.',
            'published_at' => '2026-11-19',
            'is_published' => $published,
        ];
    }

    public function test_public_channel_event_has_safe_payload_and_name(): void
    {
        $event = new NewArticleEvent(42, 'Новая статья');
        $this->assertInstanceOf(ShouldBroadcast::class, $event);
        $this->assertSame('NewArticleEvent', $event->broadcastAs());
        $channels = $event->broadcastOn();
        $this->assertCount(1, $channels);
        $this->assertInstanceOf(Channel::class, $channels[0]);
        $this->assertSame('articles', $channels[0]->name);
        $this->assertSame([
            'article' => [
                'id' => 42,
                'title' => 'Новая статья',
                'url' => route('news.show', ['article' => 42], false),
            ],
        ], $event->broadcastWith());
        $this->assertArrayNotHasKey('password', $event->broadcastWith()['article']);
    }

    public function test_published_article_broadcasts_and_draft_does_not(): void
    {
        Event::fake([NewArticleEvent::class]);
        $this->actingAs($this->moderator());

        $this->post(route('news.store'), $this->articleData('0'))
            ->assertRedirect(route('news.manage'));
        Event::assertNotDispatched(NewArticleEvent::class);

        $this->post(route('news.store'), $this->articleData('1'))
            ->assertRedirect(route('news.manage'));
        $published = Article::query()->where('is_published', true)->firstOrFail();

        Event::assertDispatched(NewArticleEvent::class, function (NewArticleEvent $event) use ($published): bool {
            return $event->articleId === $published->id
                && $event->articleTitle === $published->title;
        });
        Event::assertDispatchedTimes(NewArticleEvent::class, 1);
    }

    public function test_publishing_saved_draft_broadcasts_once(): void
    {
        Event::fake([NewArticleEvent::class]);
        $moderator = $this->moderator();
        $draft = Article::factory()->unpublished()->create();

        $this->actingAs($moderator)->put(route('news.update', $draft), $this->articleData('1'))
            ->assertRedirect(route('news.manage'));

        Event::assertDispatchedTimes(NewArticleEvent::class, 1);

        $this->put(route('news.update', $draft), $this->articleData('1'))
            ->assertRedirect(route('news.manage'));

        Event::assertDispatchedTimes(NewArticleEvent::class, 1);
    }

    public function test_layout_exposes_only_public_pusher_fields_and_vue_mount(): void
    {
        $page = $this->get(route('home'))->assertOk();
        $page->assertSee('id="app"', false);
        $page->assertSee('window.__BROADCAST_PUBLIC__', false);
        $page->assertDontSee('PUSHER_APP_SECRET', false);

        $this->assertFileExists(resource_path('js/components/App.vue'));
        $this->assertFileExists(base_path('vite.config.js'));
        $this->assertFileExists(base_path('package.json'));
    }
}
