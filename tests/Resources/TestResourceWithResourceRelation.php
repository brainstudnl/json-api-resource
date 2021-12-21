<?php

namespace Brainstud\JsonApi\Tests\Resources;

use Brainstud\JsonApi\Resources\JsonApiResource;

class TestResourceWithResourceRelation extends JsonApiResource
{
    protected function register(): array
    {
        return [
            'id' => $this->resourceObject->identifier,
            'type' => 'test_resource_with_resource_relations',
            'attributes' => [
                'title' => $this->resourceObject->title,
            ],
            'relationships' => [
                'relation_a' => [$this->resourceObject->relationA, TestResource::class],
            ]
        ];
    }
}