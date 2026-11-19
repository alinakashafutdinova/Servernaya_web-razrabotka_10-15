<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthSanctumTest extends TestCase
{
    use RefreshDatabase;

    private function newUser(): array
    {
        return [
            'name' => 'Алина',
            'email' => 'new-user@example.test',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ];
    }

    public function test_registration_form_and_login_form_include_csrf(): void
    {
        $this->get('/register')->assertOk()->assertSee('name="_token"', false)
            ->assertSee('name="password_confirmation"', false);
        $this->get('/login')->assertOk()->assertSee('name="_token"', false);
    }

    public function test_register_saves_user_with_hashed_password_and_redirects_to_login(): void
    {
        $this->post('/register', $this->newUser())->assertRedirect(route('login'));
        $user = User::firstOrFail();
        $this->assertSame('Алина', $user->name);
        $this->assertTrue(Hash::check('StrongPass123!', $user->password));
        $this->assertNotSame('StrongPass123!', $user->password);
        $this->assertGuest();
    }

    public function test_invalid_registration_and_duplicate_emails_are_rejected(): void
    {
        $data = $this->newUser();
        $data['password_confirmation'] = 'not-a-match';
        $this->post('/register', $data)->assertSessionHasErrors('password');
        $this->assertDatabaseCount('users', 0);
        $this->post('/register', $this->newUser())->assertRedirect(route('login'));
        $this->post('/register', $this->newUser())->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_login_creates_session_and_sanctum_token_then_logout_revokes(): void
    {
        $this->post('/register', $this->newUser())->assertRedirect(route('login'));
        $this->post('/login', ['email' => 'new-user@example.test', 'password' => 'StrongPass123!'])
            ->assertRedirect(route('home'));
        $this->assertAuthenticated();
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertNotNull(session('sanctum_token_id'));
        $this->get('/management')->assertForbidden()->assertSeeText('Управление статьями доступно только модератору.');
        $this->post('/logout')->assertRedirect(route('home'));
        $this->assertGuest('web');
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->app['auth']->forgetGuards();
        $this->get('/management')->assertRedirect(route('login'));
    }

    public function test_wrong_password_does_not_authenticate_or_issue_token(): void
    {
        User::create(['name' => 'Алина', 'email' => 'a@example.test', 'password' => Hash::make('StrongPass123!')]);
        $this->from('/login')->post('/login', ['email' => 'a@example.test', 'password' => 'wrong'])
            ->assertRedirect('/login')->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_guests_cannot_change_news(): void
    {
        $article = Article::factory()->create();
        foreach (['/management', '/news/create', '/news/'.$article->id.'/edit'] as $path) {
            $this->get($path)->assertRedirect(route('login'));
        }
        $this->post('/news', [])->assertRedirect(route('login'));
        $this->put('/news/'.$article->id, [])->assertRedirect(route('login'));
        $this->delete('/news/'.$article->id)->assertRedirect(route('login'));
        $this->assertDatabaseHas('articles', ['id' => $article->id]);
        $this->get('/news')->assertOk();
        $this->get('/news/'.$article->id)->assertOk();
    }

    public function test_protected_routes_use_sanctum_middleware(): void
    {
        foreach (['news.manage', 'news.create', 'news.store', 'news.edit', 'news.update', 'news.destroy', 'logout'] as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route);
            $this->assertContains('auth:sanctum', $route->gatherMiddleware());
        }
    }

    public function test_old_json_form_is_preserved(): void
    {
        $this->get('/signin')->assertOk();
        $this->post('/signin', ['name' => 'Тест', 'email' => 'demo@example.test', 'password' => 'StrongPass123!'])
            ->assertOk()->assertJsonPath('user.password', '[скрыт]');
        $this->assertDatabaseCount('users', 0);
    }
}
