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
            'relationships' => [],
        ];

        if($this->resourceObject->email){
            $data['attributes']['email']  = $this->resourceObject->email;
        }

        if($this->resourceObject->relationLoaded('posts')) {
            $data['relationships']['posts'] = [$this->resourceObject->posts, PostCollectionResource::class];
        }

        if($this->resourceObject->relationLoaded('comments')) {
            $data['relationships']['comments'] = [$this->resourceObject->comments, CommentCollectionResource::class];
        }

        if($this->resourceObject->posts->count() >= 10){
            $data['meta'] = [
                'experienced_author' => true,
            ];
        }


        return $data;
    }
}