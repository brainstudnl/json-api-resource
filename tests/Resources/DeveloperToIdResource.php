<?php

namespace Brainstud\JsonApi\Tests\Resources;

use Brainstud\JsonApi\Tests\Models\Developer;

/**
 * @property Developer $resource
 */
class DeveloperToIdResource extends DeveloperResource
{
    protected function toId(): string|int|null
    {
        return $this->resource->name;
    }
}
