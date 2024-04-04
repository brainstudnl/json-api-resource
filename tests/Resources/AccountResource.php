<?php

namespace Brainstud\JsonApi\Tests\Resources;

use Brainstud\JsonApi\Resources\JsonApiResource;

class AccountResource extends JsonApiResource
{
    protected function register(): array
    {

        $data = [
            'id' => $this->resource->identifier,
            'type' => 'accounts',
            'attributes' => [
                'name' => $this->resource->name,
            ],
            'relationships' => [
                'posts' => ['posts', PostCollectionResource::class],
                'comments' => ['comments', CommentCollectionResource::class],
            ],
        ];

        if ($this->resource->email) {
            $data['attributes']['email'] = $this->resource->email;
        }

        if ($this->resource->posts()->count() >= 10) {
            $data['meta'] = [
                'experienced_author' => true,
            ];
        }

        return $data;
    }
}
