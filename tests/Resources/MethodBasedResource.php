<?php

namespace Brainstud\JsonApi\Tests\Resources;

use Brainstud\JsonApi\Resources\JsonApiResource;
use Brainstud\JsonApi\Tests\Models\Comment;
use Illuminate\Http\Request;

/** @var Comment $resource */
class MethodBasedResource extends JsonApiResource
{
    public string $type = 'comments';

    public function toAttributes(Request $request): array
    {
        return [
            'content' => $this->resource->content,
        ];
    }

    public function toRelationships(Request $request): array
    {
        return [
            'post' => ['post', PostResource::class],
            'commenter' => ['commenter', AccountResource::class],
        ];
    }

    public function toMeta(Request $request): array
    {
        return [];
    }

    public function toLinks(Request $request): array
    {
        return [
            'show' => $this->resource->getShowUrl(),
        ];
    }
}
