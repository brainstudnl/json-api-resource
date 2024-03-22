<?php

namespace Brainstud\JsonApi\Tests\Factories;

use Brainstud\JsonApi\Tests\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'identifier' => Str::uuid(),
            'name' => $this->faker->sentence(3),
        ];
    }
}
