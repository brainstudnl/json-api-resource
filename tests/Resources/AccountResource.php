<?php

namespace Brainstud\JsonApi\Tests\Resources;

use Brainstud\JsonApi\Resources\JsonApiResource;
use Illuminate\Http\Request;

class AccountResource extends JsonApiResource
{
    protected string $type = 'accounts';

    protected function toId(): string|int|null
    {
        return $this->resource->identifier;
    }

    public function toAttributes(Request $request): array
    {
        return array_filter([
            'name' => $this->resource->name,
            'email' => $this->resource->email ?? null,
        ]);
    }

    protected function toRelationships(Request $request): array
    {
        return [
            'posts' => ['posts', PostResourceCollection::class],
            'comments' => ['comments', CommentResourceCollection::class],
        ];
    }

    protected function toMeta(Request $request): array
    {
        if ($this->resource->posts()->count() >= 10) {
            return ['experienced_author' => true];
        }

        return [];
    }
}
