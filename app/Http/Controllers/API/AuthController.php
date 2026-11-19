<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Laravel\Sanctum\PersonalAccessToken;

/** ЛР №15: копия веб-логики регистрации/входа в формате JSON и Sanctum. */
class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'email' => ['required', 'string', 'email', 'max:254', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8), 'max:72', 'confirmed'],
        ]);

        // Клиент API не может назначить себе роль moderator.
        $role = Role::firstOrCreate(['slug' => Role::READER], ['name' => 'Читатель']);
        $user = User::create([
            'role_id' => $role->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        return response()->json([
            'message' => 'Пользователь зарегистрирован.',
            'data' => $this->publicUser($user),
            'token' => $user->createToken('REST API ЛР15')->plainTextToken,
            'token_type' => 'Bearer',
        ], 201)->header('Cache-Control', 'no-store');
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $credentials['email'])->first();

        if ($user === null || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Неверная почта или пароль.',
            ], 401)->header('Cache-Control', 'no-store');
        }

        return response()->json([
            'message' => 'Авторизация прошла успешно.',
            'data' => $this->publicUser($user),
            'token' => $user->createToken('REST API ЛР15')->plainTextToken,
            'token_type' => 'Bearer',
        ])->header('Cache-Control', 'no-store');
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->publicUser($request->user()),
        ])->header('Cache-Control', 'no-store');
    }

    public function logout(Request $request): JsonResponse
    {
        // Уничтожаем только текущий API-токен, а не все устройства пользователя.
        $token = $request->user()->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->json([
            'message' => 'Выход выполнен. Текущий токен отозван.',
        ])->header('Cache-Control', 'no-store');
    }

    private function publicUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role?->slug,
        ];
    }
}
