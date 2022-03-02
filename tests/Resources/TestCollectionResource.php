<?php

namespace Brainstud\JsonApi\Tests\Resources;

use Brainstud\JsonApi\Resources\JsonApiCollectionResource;

class TestCollectionResource extends JsonApiCollectionResource
{
    public $collects = TestResourceWithRelations::class;
}