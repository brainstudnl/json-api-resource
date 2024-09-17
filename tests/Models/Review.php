<?php

namespace Brainstud\JsonApi\Tests\Models;

use Brainstud\JsonApi\Tests\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory, HasIdentifier;

    protected $fillable = [
        'identifier',
        'content',
    ];

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Developer::class, 'reviewer_id');
    }

    public function pullRequest(): BelongsTo
    {
        return $this->belongsTo(PullRequest::class);
    }

    public static function newFactory(): ReviewFactory
    {
        return ReviewFactory::new();
    }
}
