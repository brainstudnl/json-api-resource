<?php

namespace Brainstud\JsonApi\Tests\Resources;

use Brainstud\JsonApi\Resources\JsonApiResource;

class PostResource extends JsonApiResource
{
    protected function register(): array
    {
        $data =  [
            'id' => $this->resourceObject->identifier,
            'type' => 'posts',
            'attributes' => [
                'title' => $this->resourceObject->title,
                'content' => $this->resourceObject->content,
            ],
        ];

        if($this->resourceObject->relationLoaded('author')) {
            $data['relationships']['author'] = ['author', AccountResource::class];
        }

        if($this->resourceObject->relationLoaded('comments')) {
            $data['relationships']['comments'] = ['comments', CommentCollectionResource::class];
        }

        if($this->resourceObject->url){
            $data['links'] = [
                'view' => [
                    'href' => $this->resourceObject->url,
                ],
            ];
        }

        return $data;
    }
}