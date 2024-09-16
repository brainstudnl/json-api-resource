<?php

namespace Brainstud\JsonApi\Tests\Resources;

use Brainstud\JsonApi\Resources\JsonApiResource;

class PostResource extends JsonApiResource
{
    protected function register(): array
    {
        $data = [
            'id' => $this->resource->identifier,
            'type' => 'posts',
            'attributes' => [
                'title' => $this->resource->title,
                'content' => $this->resource->content,
            ],
            'relationships' => [
                'author' => ['author', AccountResource::class],
                'comments' => ['comments', CommentResourceCollection::class],
            ],
        ];

        if ($this->resource->url) {
            $data['links'] = [
                'view' => [
                    'href' => $this->resource->url,
                ],
            ];
        }

        return $data;
    }
}
