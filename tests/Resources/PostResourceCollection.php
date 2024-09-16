<?php

namespace Brainstud\JsonApi\Tests\Resources;

use Brainstud\JsonApi\Resources\JsonApiResourceCollection;

class PostResourceCollection extends JsonApiResourceCollection
{
    public $collects = PostResource::class;
}
