<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $reader = Role::firstOrCreate(['slug' => Role::READER], ['name' => 'Читатель']);
        Role::firstOrCreate(['slug' => Role::MODERATOR], ['name' => 'Модератор']);
        User::whereNull('role_id')->update(['role_id' => $reader->id]);
    }
}
