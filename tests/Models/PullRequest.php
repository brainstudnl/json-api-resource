<?php

namespace Brainstud\JsonApi\Tests\Models;

use Brainstud\JsonApi\Tests\Factories\PullRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PullRequest extends Model
{
    use HasFactory, HasIdentifier;

    protected $fillable = [
        'identifier',
        'title',
        'description',
    ];

    public function developer(): BelongsTo
    {
        return $this->belongsTo(Developer::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public static function newFactory(): PullRequestFactory
    {
        return PullRequestFactory::new();
    }

    public function getShowUrl(): string
    {
        return 'https://jsonapi.brainstud.dev/reviews/'.$this->identifier;
    }
}
