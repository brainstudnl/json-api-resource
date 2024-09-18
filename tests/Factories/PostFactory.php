<?php

namespace Brainstud\JsonApi\Tests\Factories;

use Brainstud\JsonApi\Tests\Models\Account;
use Brainstud\JsonApi\Tests\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @method Collection<int, Post>|Post create($attributes = [], ?Model $parent = null)
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'identifier' => (string) Str::uuid(),
            'title' => $this->faker->sentence(3),
            'content' => implode(' ', $this->faker->paragraphs(4)),
        ];
    }

    public function configure(): self
    {
        return $this->afterMaking(function (Post $account) {
            $account->author_id ??= Account::factory()->create()->id;
        });
    }
}
