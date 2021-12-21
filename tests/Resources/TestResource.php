<?php

namespace Brainstud\JsonApi\Tests\Resources;

use Brainstud\JsonApi\Resources\JsonApiResource;

class TestResource extends JsonApiResource
{
    protected function register(): array
    {
        return [
            'id' => $this->resourceObject->identifier,
            'type' => 'test_resource',
            'attributes' => [
                'title' => $this->resourceObject->title,
            ],
        ];
    }
}