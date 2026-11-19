<?php

namespace Tests\Feature;

use App\Mail\ArticleCreatedMail;
use App\Models\Article;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ArticleMailTest extends TestCase
{
    use RefreshDatabase;

    private function person(string $slug, string $email): User
    {
        $role = Role::firstOrCreate(
            ['slug' => $slug],
            ['name' => $slug === Role::MODERATOR ? 'Модератор' : 'Читатель']
        );

        return User::create([
            'name' => 'Проверка',
            'email' => $email,
            'password' => Hash::make('StrongPass123!'),
            'role_id' => $role->id,
        ]);
    }

    private function articleData(): array
    {
        return [
            'title' => 'Новая статья про Blade',
            'summary' => 'Полезная заметка об отправке уведомлений редактору.',
            'content' => 'Текст новой статьи с достаточной длиной для проверки.',
            'published_at' => '2026-11-19',
            'is_published' => '1',
        ];
    }

    public function test_moderator_receives_mail_after_article_is_saved(): void
    {
        Mail::fake();
        $moderator = $this->person(Role::MODERATOR, 'author@example.test');
        $this->actingAs($moderator)
            ->post(route('news.store'), $this->articleData())
            ->assertRedirect(route('news.manage'));

        $article = Article::firstOrFail();
        Mail::assertSent(ArticleCreatedMail::class, function (ArticleCreatedMail $mail) use ($article, $moderator) {
            return $mail->article->is($article) && $mail->hasTo($moderator->email);
        });
        Mail::assertSent(ArticleCreatedMail::class, 1);
        $this->assertDatabaseHas('articles', ['title' => $this->articleData()['title']]);
    }

    public function test_every_moderator_receives_one_message_but_reader_does_not(): void
    {
        Mail::fake();
        $moderator = $this->person(Role::MODERATOR, 'mod1@example.test');
        $this->person(Role::MODERATOR, 'mod2@example.test');
        $this->person(Role::READER, 'reader@example.test');

        $this->actingAs($moderator)->post(route('news.store'), $this->articleData());
        Mail::assertSent(ArticleCreatedMail::class, 2);
        Mail::assertSent(ArticleCreatedMail::class, fn ($mail) => $mail->hasTo('mod1@example.test'));
        Mail::assertSent(ArticleCreatedMail::class, fn ($mail) => $mail->hasTo('mod2@example.test'));
        Mail::assertNotSent(ArticleCreatedMail::class, fn ($mail) => $mail->hasTo('reader@example.test'));
    }

    public function test_draft_article_also_generates_notification(): void
    {
        Mail::fake();
        $moderator = $this->person(Role::MODERATOR, 'draft-mod@example.test');
        $data = $this->articleData();
        $data['is_published'] = '0';
        $this->actingAs($moderator)->post(route('news.store'), $data)->assertRedirect();
        $this->assertFalse(Article::firstOrFail()->is_published);
        Mail::assertSent(ArticleCreatedMail::class, 1);
    }

    public function test_invalid_article_does_not_send_message_or_create_entry(): void
    {
        Mail::fake();
        $moderator = $this->person(Role::MODERATOR, 'invalid-mod@example.test');
        $data = $this->articleData();
        $data['title'] = '';
        $this->actingAs($moderator)->post(route('news.store'), $data)
            ->assertSessionHasErrors('title');
        $this->assertSame(0, Article::count());
        Mail::assertNothingSent();
    }

    public function test_reader_cannot_create_article_or_send_mail(): void
    {
        Mail::fake();
        $reader = $this->person(Role::READER, 'no-rights@example.test');
        $this->person(Role::MODERATOR, 'other-mod@example.test');
        $this->actingAs($reader)->post(route('news.store'), $this->articleData())->assertForbidden();
        $this->assertSame(0, Article::count());
        Mail::assertNothingSent();
    }

    public function test_changes_and_removal_do_not_send_new_article_mail(): void
    {
        Mail::fake();
        $moderator = $this->person(Role::MODERATOR, 'edit-mod@example.test');
        $article = Article::factory()->create();
        $this->actingAs($moderator)->put(route('news.update', $article), $this->articleData())->assertRedirect();
        $this->delete(route('news.destroy', $article))->assertRedirect();
        Mail::assertNothingSent();
    }

    public function test_email_template_contains_article_and_escapes_html(): void
    {
        $article = Article::factory()->make([
            'id' => 100,
            'title' => '<script>alert(1)</script>',
            'summary' => 'Описание нового материала',
            'published_at' => '2026-11-19',
        ]);
        $html = (new ArticleCreatedMail($article))->render();
        $this->assertStringContainsString('Добавлена новая статья', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('Описание нового материала', $html);
        $this->assertStringContainsString('/news/100', $html);
    }

}
