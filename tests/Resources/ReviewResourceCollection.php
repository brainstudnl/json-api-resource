<?php

namespace Brainstud\JsonApi\Tests\Resources;

use Brainstud\JsonApi\Resources\JsonApiResourceCollection;

class ReviewResourceCollection extends JsonApiResourceCollection
{
    public $collects = ReviewResource::class;
}
