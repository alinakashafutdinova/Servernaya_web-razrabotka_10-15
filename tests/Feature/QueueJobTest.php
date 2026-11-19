<?php

namespace Tests\Feature;

use App\Jobs\VeryLongJob;
use App\Mail\ArticleCreatedMail;
use App\Models\Article;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QueueJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_jobs_and_failed_jobs_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('jobs'));
        $this->assertTrue(Schema::hasTable('failed_jobs'));
    }

    public function test_creating_article_queues_mail_until_worker_processes_job(): void
    {
        config()->set('queue.default', 'database');
        Mail::fake();

        $role = Role::firstOrCreate(['slug' => Role::MODERATOR], ['name' => 'Модератор']);
        $moderator = User::create([
            'role_id' => $role->id,
            'name' => 'Проверяющий',
            'email' => 'teacher@example.test',
            'password' => Hash::make('Password12345!'),
        ]);

        $this->actingAs($moderator)->post(route('news.store'), [
            'title' => 'ЛР10: очередь писем',
            'summary' => 'Проверка сохранения задачи в базе данных.',
            'content' => 'После обработки очереди модератор получит письмо.',
            'published_at' => '2026-11-19',
            'is_published' => '1',
        ])->assertRedirect(route('news.manage'));

        $this->assertSame(1, Article::count());
        // Две задачи: письмо №10 и трансляция опубликованной статьи №11.
        $this->assertDatabaseCount('jobs', 2);
        Mail::assertNothingSent();

        $exit = Artisan::call('queue:work', [
            'connection' => 'database',
            '--once' => true,
            '--tries' => 1,
            '--no-interaction' => true,
        ]);
        $this->assertSame(0, $exit);
        $secondExit = Artisan::call('queue:work', [
            'connection' => 'database',
            '--once' => true,
            '--tries' => 1,
            '--no-interaction' => true,
        ]);
        $this->assertSame(0, $secondExit);
        $this->assertDatabaseCount('jobs', 0);
        Mail::assertSent(ArticleCreatedMail::class, fn ($mail) => $mail->hasTo('teacher@example.test'));
    }

    public function test_job_ignores_article_deleted_before_processing(): void
    {
        Mail::fake();
        (new VeryLongJob(999999999))->handle();
        Mail::assertNothingSent();
    }
}
