<?php

namespace Brainstud\JsonApi\Tests\Resources;

use Brainstud\JsonApi\Resources\JsonApiResource;

class CommentResource extends JsonApiResource
{
    protected function register(): array
    {
        $data = [
            'id' => $this->resourceObject->identifier,
            'type' => 'comments',
            'attributes' => [
                'content' => $this->resourceObject->content,
            ],
            'relationships' => [
                'post' => ['post', PostResource::class],
                'commenter' => ['commenter', AccountResource::class],
            ],
        ];

        return $data;
    }
}
