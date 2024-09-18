<?php

namespace Brainstud\JsonApi\Tests\Resources;

use Brainstud\JsonApi\Resources\JsonApiResourceCollection;

class DeveloperResourceCollection extends JsonApiResourceCollection
{
    public $collects = DeveloperResource::class;
}
