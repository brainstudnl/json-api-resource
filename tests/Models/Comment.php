<?php

namespace Brainstud\JsonApi\Tests\Models;

use Brainstud\JsonApi\Tests\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @method static CommentFactory factory($count = null, $state = [])
 */
class Comment extends Model
{
    use HasFactory, HasIdentifier;

    protected $fillable = [
        'identifier',
        'content',
    ];

    public function commenter(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function post(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public static function newFactory(): CommentFactory
    {
        return CommentFactory::new();
    }

    public function getShowUrl(): string
    {
        return 'https://jsonapi.brainstud.dev/comments/'.$this->identifier;
    }
}
