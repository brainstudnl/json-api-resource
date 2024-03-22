<?php

namespace Brainstud\JsonApi\Tests\Resources;

use Brainstud\JsonApi\Resources\JsonApiResource;

class AccountResource extends JsonApiResource
{
    protected function register(): array
    {

        $data = [
            'id' => $this->resourceObject->identifier,
            'type' => 'accounts',
            'attributes' => [
                'name' => $this->resourceObject->name,
            ],
            'relationships' => [
                'posts' => ['posts', PostCollectionResource::class],
                'comments' => ['comments', CommentCollectionResource::class],
            ],
        ];

        if ($this->resourceObject->email) {
            $data['attributes']['email'] = $this->resourceObject->email;
        }

        if ($this->resourceObject->posts()->count() >= 10) {
            $data['meta'] = [
                'experienced_author' => true,
            ];
        }

        return $data;
    }
}
