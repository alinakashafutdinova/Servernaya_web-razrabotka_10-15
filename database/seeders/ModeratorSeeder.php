<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ModeratorSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::where('slug', Role::MODERATOR)->firstOrFail();
        $email = env('MODERATOR_EMAIL', 'moderator@example.test') ?: 'moderator@example.test';
        $password = env('MODERATOR_PASSWORD');
        if (app()->environment('production') && (!$password || strlen($password) < 12)) {
            throw new \RuntimeException('Для установки модератора укажите MODERATOR_PASSWORD (не менее 12 символов) в .env.');
        }
        $password = $password ?: bin2hex(random_bytes(18));
        $user = User::where('email', $email)->first();

        if ($user === null) {
            User::create([
                'name' => 'Модератор',
                'email' => $email,
                'role_id' => $role->id,
                'password' => Hash::make($password),
            ]);
            return;
        }

        // Не повышаем права существующего читателя по совпадению адреса почты.
        if ($user->role_id !== $role->id) {
            throw new \RuntimeException('Указанный адрес модератора уже занят другим пользователем.');
        }

        if (env('MODERATOR_PASSWORD')) {
            $user->update(['password' => Hash::make($password)]);
        }
    }
}
