<?php

namespace Brainstud\JsonApi\Tests\Resources;

use Brainstud\JsonApi\Resources\JsonApiResource;
use Illuminate\Http\Request;

class CommentResource extends JsonApiResource
{
    protected string $type = 'comments';

    protected function toId(): string|int|null
    {
        return $this->resource->identifier;
    }

    public function toAttributes(Request $request): array
    {
        return [
            'content' => $this->resource->content,
        ];
    }

    protected function toRelationships(Request $request): array
    {
        return [
            'post' => ['post', PostResource::class],
            'commenter' => ['commenter', AccountResource::class],
        ];
    }

    protected function toMeta(Request $request): array
    {
        if ($request->query('meta') === 'merge_data_test') {
            return [
                $this->mergeWhen($this->resourceDepth === 1, ['firstResourceData' => true]),
                $this->mergeWhen($this->resourceDepth === 3, ['secondResourceData' => true]),
            ];
        }

        return [];
    }
}
