<?php

namespace Brainstud\JsonApi\Tests\Resources;

use Brainstud\JsonApi\Resources\JsonApiResourceCollection;

class MethodBasedResourceCollection extends JsonApiResourceCollection
{
    public $collects = MethodBasedResource::class;
}
