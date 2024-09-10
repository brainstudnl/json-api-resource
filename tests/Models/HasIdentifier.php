<?php

namespace Brainstud\JsonApi\Tests\Models;

trait HasIdentifier
{
    public string $identifierAttributeName = 'identifier';

    public function getRouteKeyName(): string
    {
        return $this->identifierAttributeName;
    }
}
