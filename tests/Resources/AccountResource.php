<?php

namespace Brainstud\JsonApi\Tests\Resources;

use Brainstud\JsonApi\Resources\JsonApiResource;

class AccountResource extends JsonApiResource
{

    protected function register(): array
    {

        $data = [
            'relationships' => [
                'posts' => ['posts', PostCollectionResource::class],
                'comments' => ['comments', CommentCollectionResource::class],
            ],
        ];

        if($this->resourceObject->posts()->count() >= 10){
            $data['meta'] = [
                'experienced_author' => true,
            ];
        }


        return $data;
    }
}
