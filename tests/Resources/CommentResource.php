<?php

namespace Brainstud\JsonApi\Tests\Resources;

use Brainstud\JsonApi\Resources\JsonApiResource;

class CommentResource extends JsonApiResource
{
    protected function register(): array
    {
        $data =  [
            'id' => $this->resourceObject->identifier,
            'type' => 'comments',
            'attributes' => [
                'content' => $this->resourceObject->content,
            ],
        ];

        if($this->resourceObject->relationLoaded('post')) {
            $data['relationships']['post'] = [$this->resourceObject->post, PostResource::class];
        }

        if($this->resourceObject->relationLoaded('commenter')) {
            $data['relationships']['commenter'] = [$this->resourceObject->commenter, AccountResource::class];
        }

        return $data;
    }
}