<?php

namespace Brainstud\JsonApi\Tests\Resources;

use Brainstud\JsonApi\Resources\JsonApiResource;

class TestResourceWithDescriptionA extends JsonApiResource
{
    protected function register(): array
    {
        return [
            'id' => $this->resourceObject->identifier,
            'type' => 'test_resource_with_description_a',
            'attributes' => [
                'title' => $this->resourceObject->title,
                'description' => $this->resourceObject->description,
            ],
            'relationships' => [
                'relation_a' => [$this->resourceObject->relationA, TestResourceWithDescriptionB::class],
            ]
        ];
    }
}