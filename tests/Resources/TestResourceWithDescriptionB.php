<?php

namespace Brainstud\JsonApi\Tests\Resources;

use Brainstud\JsonApi\Resources\JsonApiResource;

class TestResourceWithDescriptionB extends JsonApiResource
{
    protected function register(): array
    {
        return [
            'id' => $this->resourceObject->identifier,
            'type' => 'test_resource_with_description_b',
            'attributes' => [
                'title' => $this->resourceObject->title,
                'description' => $this->resourceObject->description,
            ],
            'relationships' => [
                'relation_a' => [$this->resourceObject->relationA, TestResourceWithDescriptionA::class],
            ]
        ];
    }
}