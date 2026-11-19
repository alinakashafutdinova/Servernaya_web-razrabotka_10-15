<?php

namespace Database\Seeders;

use App\Models\Article;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([RoleSeeder::class, ModeratorSeeder::class]);
        if (Article::query()->doesntExist()) {
            Article::factory()->count(12)->create();
        }
    }
}
