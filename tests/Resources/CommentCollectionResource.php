<?php

namespace Brainstud\JsonApi\Tests\Resources;

use Brainstud\JsonApi\Resources\JsonApiCollectionResource;

class CommentCollectionResource extends JsonApiCollectionResource
{
    public $collects = CommentResource::class;
}