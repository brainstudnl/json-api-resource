<?php

namespace Brainstud\JsonApi\Tests\Resources;

use Brainstud\JsonApi\Resources\JsonApiResource;

class CommentResource extends JsonApiResource
{
    protected function register(): array
    {
        $data = [
            'id' => $this->resource->identifier,
            'type' => 'comments',
            'attributes' => [
                'content' => $this->resource->content,
            ],
            'relationships' => [
                'post' => ['post', PostResource::class],
                'commenter' => ['commenter', AccountResource::class],
            ],
        ];

        return $data;
    }
}
