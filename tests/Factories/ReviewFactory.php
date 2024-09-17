<?php

namespace Brainstud\JsonApi\Tests\Factories;

use Brainstud\JsonApi\Tests\Models\Developer;
use Brainstud\JsonApi\Tests\Models\PullRequest;
use Brainstud\JsonApi\Tests\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        return [
            'identifier' => (string) Str::uuid(),
            'content' => $this->faker->paragraph(),
        ];
    }

    public function configure(): self
    {
        return $this->afterMaking(function (Review $review) {
            $review->reviewer_id ??= Developer::factory()->create()->id;
            $review->pull_request_id ??= PullRequest::factory()->create()->id;
        });
    }
}
