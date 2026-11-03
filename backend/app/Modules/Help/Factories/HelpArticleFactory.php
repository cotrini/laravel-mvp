<?php

namespace App\Modules\Help\Factories;

use App\Modules\Help\Models\HelpArticle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<HelpArticle>
 */
class HelpArticleFactory extends Factory
{
    protected $model = HelpArticle::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(5)),
            'category' => 'tutorial',
            'excerpt' => fake()->sentence(12),
            'body' => fake()->paragraph(),
            'video_path' => null,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ];
    }

    public function withVideo(): static
    {
        return $this->state(fn (): array => [
            'video_path' => 'HelpCenter/'.fake()->uuid().'.mp4',
        ]);
    }
}
