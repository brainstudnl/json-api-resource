<?php

namespace Brainstud\JsonApi\Tests\Resources;

use Brainstud\JsonApi\Resources\JsonApiResource;
use Illuminate\Http\Request;

class PullRequestResource extends JsonApiResource
{
    protected string $type = 'pull_requests';

    protected function toAttributes(Request $request): array
    {
        return [
            'title' => $this->resource->title,
            'description' => $this->resource->description,
        ];
    }

    protected function toRelationships(Request $request): array
    {
        return [
            'developer' => ['developer', DeveloperResource::class],
            'reviews' => ['reviews', ReviewResourceCollection::class],
        ];
    }

    protected function toLinks(Request $request): array
    {
        return [
            'view' => ['href' => $this->resource->getShowUrl()],
        ];
    }
}
