<?php

namespace Database\Factories;

use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(5),
            'summary' => $this->faker->text(170),
            'content' => implode("\n\n", $this->faker->paragraphs(4)),
            'published_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'is_published' => true,
        ];
    }

    public function unpublished(): self
    {
        return $this->state(fn () => ['is_published' => false]);
    }
}
