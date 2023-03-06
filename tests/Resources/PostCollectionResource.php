<?php

namespace Brainstud\JsonApi\Tests\Resources;

use Brainstud\JsonApi\Resources\JsonApiCollectionResource;

class PostCollectionResource extends JsonApiCollectionResource
{
    public $collects = PostResource::class;
}