<?php

namespace Brainstud\JsonApi\Tests\Resources;

use Brainstud\JsonApi\Resources\JsonApiResource;
use Illuminate\Database\Eloquent\Model;

/**
 * @property Model $resourceObject
 */
class TestResourceWithRelations extends JsonApiResource
{
    protected function register(): array
    {
        return [
            'id' => $this->resourceObject->identifier,
            'type' => 'test_resource_with_relations',
            'attributes' => [
                'title' => $this->resourceObject->title,
            ],
            'relationships' => [
                'relation_a' => [ 'relationA', TestResource::class ],
                'relation_b' => [ 'relationB', TestCollectionResource::class ],
            ]
        ];
    }
}