<?php

namespace Brainstud\JsonApi\Tests\Resources;

use Brainstud\JsonApi\Resources\JsonApiResourceCollection;

class PullRequestResourceCollection extends JsonApiResourceCollection
{
    public $collects = PullRequestResource::class;
}
