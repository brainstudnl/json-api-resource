<?php


namespace Brainstud\JsonApi\Tests\Factories;

use Brainstud\JsonApi\Tests\Models\Account;
use Brainstud\JsonApi\Tests\Models\Comment;
use Brainstud\JsonApi\Tests\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CommentFactory extends Factory {

    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'identifier' => (string) Str::uuid(),
            'content' => $this->faker->paragraph(),
        ];
    }

    public function configure(): self
    {
        return $this->afterMaking(function (Comment $comment) {
            $comment->account_id ??= Account::factory()->create()->id;
            $comment->post_id ??= Post::factory()->create()->id;
        });
    }
}