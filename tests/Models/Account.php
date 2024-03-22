<?php

namespace Brainstud\JsonApi\Tests\Models;

use Brainstud\JsonApi\Tests\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory, HasIdentifier;

    protected $fillable = [
        'identifier',
        'name',
        'email',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'author_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    protected static function newFactory(): AccountFactory
    {
        return AccountFactory::new();
    }
}
