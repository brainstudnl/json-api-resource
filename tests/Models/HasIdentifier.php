<?php
namespace Brainstud\JsonApi\Tests\Models;



trait HasIdentifier
{
    public function getRouteKeyName(): string
    {
        return 'identifier';
    }
}
