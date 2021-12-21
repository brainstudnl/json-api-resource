<?php

namespace Brainstud\JsonApi\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestModel extends Model
{
    protected $guarded = [];

    public function relationA(): BelongsTo
    {
        return $this->belongsTo(TestModel::class);
    }

    public function relationB(): HasMany
    {
        return $this->hasMany(TestModel::class);
    }
}