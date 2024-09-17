<?php

namespace Brainstud\JsonApi\Tests\Factories;

use Brainstud\JsonApi\Tests\Models\Developer;
use Brainstud\JsonApi\Tests\Models\PullRequest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PullRequestFactory extends Factory
{
    protected $model = PullRequest::class;

    public function definition(): array
    {
        return [
            'identifier' => (string) Str::uuid(),
            'title' => $this->faker->sentence(3, true),
            'description' => $this->faker->sentences(3, true),
        ];
    }

    public function configure(): self
    {
        return $this->afterMaking(function (PullRequest $pullRequest) {
            $pullRequest->developer_id ??= Developer::factory()->create()->id;
        });
    }
}
