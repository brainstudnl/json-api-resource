<?php

namespace Brainstud\JsonApi\Tests\Resources;

use Brainstud\JsonApi\Resources\JsonApiResource;
use Illuminate\Http\Request;

class PostResource extends JsonApiResource
{
    protected string $type = 'posts';

    protected function toId(): string|int|null
    {
        return $this->resource->identifier;
    }

    public function toAttributes(Request $request): array
    {
        return [
            'title' => $this->resource->title,
            'content' => $this->resource->content,
        ];
    }

    protected function toRelationships(Request $request): array
    {
        return [
            'author' => ['author', AccountResource::class],
            'comments' => ['comments', CommentResourceCollection::class],
        ];
    }

    protected function toLinks(Request $request): array
    {
        if ($this->resource->url) {
            return [
                'view' => [
                    'href' => $this->resource->url,
                ],
            ];
        }

        return [];
    }
}
