<?php

namespace Brainstud\JsonApi\Tests\Factories;

use Brainstud\JsonApi\Tests\Models\Developer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @method Collection<int, Developer>|Developer create($attributes = [], ?Model $parent = null)
 */
class DeveloperFactory extends Factory
{
    protected $model = Developer::class;

    public function definition(): array
    {
        return [
            'identifier' => Str::uuid(),
            'name' => $this->faker->name,
        ];
    }
}
