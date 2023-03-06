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
            'relationships' => [
                'author' => ['author', AccountResource::class],
                'comments' => ['comments', CommentCollectionResource::class],
            ]
        ];

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