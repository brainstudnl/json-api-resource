<?php

namespace Brainstud\JsonApi\Tests\Resources;

use Brainstud\JsonApi\Resources\JsonApiResourceCollection;

class CommentResourceCollection extends JsonApiResourceCollection
{
    public $collects = CommentResource::class;
}
