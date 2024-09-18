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

        if (request()->query('meta') === 'merge_data_test') {
            $data['meta'] = [
                $this->mergeWhen($this->resourceDepth === 1, ['firstResourceData' => true]),
                $this->mergeWhen($this->resourceDepth === 3, ['secondResourceData' => true]),
            ];
        }

        return $data;
    }
}
