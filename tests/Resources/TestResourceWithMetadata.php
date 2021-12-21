<?php

namespace Brainstud\JsonApi\Tests\Resources;

use Brainstud\JsonApi\Resources\JsonApiResource;

class TestResourceWithMetadata extends JsonApiResource
{
    protected function register(): array
    {
        return [
            'id' => $this->resourceObject->identifier,
            'type' => 'test_resource_with_metadata',
            'attributes' => [
                'title' => $this->resourceObject->title,
            ],
            'meta' => [
                'test_count' => $this->resourceObject->test_count,
            ],
            'links' => [
                'edit' => $this->resourceObject->edit_link,
            ]
        ];
    }
}