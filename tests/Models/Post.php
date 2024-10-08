<?php

namespace Brainstud\JsonApi\Tests\Models;

use Brainstud\JsonApi\Tests\Factories\PostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @method static PostFactory factory($count = null, $state = [])
 */
class Post extends Model
{
    use HasFactory, HasIdentifier;

    protected $fillable = [
        'identifier',
        'title',
        'content',
    ];

    protected $hidden = [
        'url',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'author_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public static function newFactory(): PostFactory
    {
        return PostFactory::new();
    }
}
