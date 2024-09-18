<?php

namespace Brainstud\JsonApi\Tests\Models;

use Brainstud\JsonApi\Tests\Factories\DeveloperFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @method static DeveloperFactory factory($count = null, $state = [])
 */
class Developer extends Model
{
    use HasFactory, HasIdentifier;

    protected $fillable = [
        'identifier',
        'name',
        'email',
    ];

    public function pullRequests(): HasMany
    {
        return $this->hasMany(PullRequest::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    protected static function newFactory(): DeveloperFactory
    {
        return DeveloperFactory::new();
    }
}
