<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserSessionController extends Controller
{
    public function registerForm(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'email' => ['required', 'string', 'email', 'max:254', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8), 'max:72', 'confirmed'],
        ]);

        $reader = Role::firstOrCreate(['slug' => Role::READER], ['name' => 'Читатель']);

        User::create([
            'role_id' => $reader->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        return redirect()->route('login')->with('status', 'Профиль создан. Теперь войдите в аккаунт.');
    }

    public function loginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->attempt($credentials)) {
            return back()->withErrors(['email' => 'Неверная почта или пароль.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        // Sanctum создаёт отдельный токен входа, его идентификатор хранится в сессии.
        $token = $request->user()->createToken('Вход на сайт');
        $request->session()->put('sanctum_token_id', $token->accessToken->id);

        return redirect()->route('home')->with('welcome', 'Вход выполнен! Добро пожаловать в ваш аккаунт.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $tokenId = $request->session()->pull('sanctum_token_id');
        if ($tokenId !== null && $request->user() !== null) {
            $request->user()->tokens()->whereKey($tokenId)->delete();
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
