<?php

namespace Brainstud\JsonApi\Tests\Factories;

use Brainstud\JsonApi\Tests\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @method Collection<int, Account>|Account create($attributes = [], ?Model $parent = null)
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'identifier' => Str::uuid(),
            'name' => $this->faker->name,
        ];
    }
}
