<?php

namespace Tests\Feature;

use App\Mail\DailyStatisticsMail;
use App\Models\Article;
use App\Models\ArticleView;
use App\Models\Comment;
use App\Models\DailyReportDelivery;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DailyStatisticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function person(string $role, string $email): User
    {
        $model = Role::firstOrCreate(
            ['slug' => $role],
            ['name' => $role === Role::MODERATOR ? 'Модератор' : 'Читатель'],
        );

        return User::create([
            'role_id' => $model->id,
            'name' => 'Участник статистики',
            'email' => $email,
            'password' => Hash::make('StrongPassword2026!'),
        ]);
    }

    public function test_tables_for_views_and_mail_delivery_are_migrated(): void
    {
        $this->assertTrue(Schema::hasTable('article_views'));
        $this->assertTrue(Schema::hasColumns('article_views', ['article_id', 'url', 'viewed_at']));
        $this->assertTrue(Schema::hasTable('daily_report_deliveries'));
        $this->assertTrue(Schema::hasColumns('daily_report_deliveries', ['report_date', 'moderator_id', 'sent_at']));
    }

    public function test_middleware_counts_only_successful_news_openings_and_drops_query_string(): void
    {
        $article = Article::factory()->create();
        $draft = Article::factory()->unpublished()->create();

        $this->get('/')->assertOk();
        $this->get('/news')->assertOk();
        $this->assertSame(0, ArticleView::count());

        $this->get(route('news.show', $article).'?auth_token=not-for-database')->assertOk();
        $this->get(route('news.show', $article))->assertOk();
        $this->get('/news/999999')->assertNotFound();
        $this->get(route('news.show', $draft))->assertNotFound();

        $this->assertSame(2, ArticleView::count());
        $this->assertSame('/news/'.$article->id, ArticleView::firstOrFail()->url);
        $this->assertStringNotContainsString('auth_token', ArticleView::firstOrFail()->url);
        $this->assertDatabaseHas('article_views', ['article_id' => $article->id]);
    }

    public function test_cached_article_views_are_counted_for_every_real_request(): void
    {
        $article = Article::factory()->create();
        $this->get(route('news.show', $article))->assertOk();
        $this->get(route('news.show', $article))->assertOk();

        $this->assertSame(2, ArticleView::where('article_id', $article->id)->count());
    }

    public function test_daily_command_counts_only_selected_date_and_sends_to_every_moderator(): void
    {
        $day = CarbonImmutable::parse('2026-11-19 14:00:00', 'Europe/Moscow');
        $this->travelTo($day);

        $mod1 = $this->person(Role::MODERATOR, 'daily-mod1@example.test');
        $mod2 = $this->person(Role::MODERATOR, 'daily-mod2@example.test');
        $reader = $this->person(Role::READER, 'daily-reader@example.test');
        $article = Article::factory()->create();

        $this->get(route('news.show', $article))->assertOk();
        $this->get(route('news.show', $article))->assertOk();
        ArticleView::create([
            'article_id' => $article->id,
            'url' => '/news/'.$article->id,
            'viewed_at' => $day->subDay(),
        ]);

        $article->comments()->create(['user_id' => $reader->id, 'body' => 'Новый комментарий']);
        $older = $article->comments()->create(['user_id' => $reader->id, 'body' => 'Вчерашний комментарий']);
        DB::table('comments')->where('id', $older->id)->update([
            'created_at' => $day->subDay()->format('Y-m-d H:i:s'),
            'updated_at' => $day->subDay()->format('Y-m-d H:i:s'),
        ]);

        $this->assertSame(0, Artisan::call('stats:daily', ['--date' => '2026-11-19']));

        Mail::assertSent(DailyStatisticsMail::class, 2);
        Mail::assertSent(DailyStatisticsMail::class, fn (DailyStatisticsMail $mail) =>
            $mail->hasTo($mod1->email) && $mail->reportDate === '2026-11-19'
                && $mail->viewsCount === 2 && $mail->commentsCount === 1);
        Mail::assertSent(DailyStatisticsMail::class, fn (DailyStatisticsMail $mail) =>
            $mail->hasTo($mod2->email));
        Mail::assertNotSent(DailyStatisticsMail::class, fn (DailyStatisticsMail $mail) =>
            $mail->hasTo($reader->email));

        $this->assertSame(2, DailyReportDelivery::count());
        $this->assertDatabaseHas('daily_report_deliveries', ['moderator_id' => $mod1->id]);
    }

    public function test_duplicate_daily_sends_are_skipped_but_force_can_repeat_for_demo(): void
    {
        $this->person(Role::MODERATOR, 'daily-once@example.test');

        $this->assertSame(0, Artisan::call('stats:daily'));
        $this->assertSame(0, Artisan::call('stats:daily'));
        Mail::assertSent(DailyStatisticsMail::class, 1);
        $this->assertDatabaseCount('daily_report_deliveries', 1);

        $this->assertSame(0, Artisan::call('stats:daily', ['--force' => true]));
        Mail::assertSent(DailyStatisticsMail::class, 2);
        $this->assertDatabaseCount('daily_report_deliveries', 1);
    }

    public function test_invalid_date_is_rejected_without_sending_email(): void
    {
        $this->person(Role::MODERATOR, 'invalid-date@example.test');

        $this->assertSame(1, Artisan::call('stats:daily', ['--date' => '2026-02-30']));
        $this->assertSame(1, Artisan::call('stats:daily', ['--date' => 'not-a-date']));
        Mail::assertNothingSent();
    }

    public function test_only_moderator_can_manually_send_report_from_admin_panel(): void
    {
        $moderator = $this->person(Role::MODERATOR, 'manual-mod@example.test');
        $reader = $this->person(Role::READER, 'manual-reader@example.test');

        $this->post(route('reports.daily.send'))->assertRedirect(route('login'));

        $this->actingAs($reader)->post(route('reports.daily.send'))->assertForbidden();
        Mail::assertNothingSent();

        $this->actingAs($moderator)->get(route('news.manage'))
            ->assertOk()->assertSeeText('Отправить статистику за сегодня')
            ->assertSee('name="_token"', false);

        $this->post(route('reports.daily.send'))
            ->assertRedirect(route('news.manage'))
            ->assertSessionHas('status');

        Mail::assertSent(DailyStatisticsMail::class, 1);
        $this->assertDatabaseCount('daily_report_deliveries', 1);
    }

    public function test_schedule_registers_every_minute_command_and_mail_renders(): void
    {
        config()->set('daily_reports.demo_every_minute', true);

        $this->assertSame(0, Artisan::call('schedule:list'));
        $this->assertStringContainsString('stats:daily', Artisan::output());
        // Проверяем именно cron-выражение, а не форматированный вывод CLI.
        $events = app(\Illuminate\Console\Scheduling\Schedule::class)->events();
        $daily = collect($events)->first(
            static fn ($event): bool => str_contains($event->command ?? '', 'stats:daily')
        );
        $this->assertNotNull($daily);
        $this->assertSame('* * * * *', $daily->expression);

        $html = (new DailyStatisticsMail('2026-11-19', 14, 3))->render();
        $this->assertStringContainsString('19', $html);
        $this->assertStringContainsString('14', $html);
        $this->assertStringContainsString('3', $html);
        $this->assertStringContainsString('Статистика', (new DailyStatisticsMail('2026-11-19', 14, 3))->build()->subject);
    }
}
