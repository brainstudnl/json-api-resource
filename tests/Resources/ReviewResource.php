<?php

namespace Brainstud\JsonApi\Tests\Resources;

use Brainstud\JsonApi\Resources\JsonApiResource;
use Illuminate\Http\Request;

class ReviewResource extends JsonApiResource
{
    protected string $type = 'reviews';

    protected function toAttributes(Request $request): array
    {
        return [
            'content' => $this->resource->content,
        ];
    }

    protected function toRelationships(Request $request): array
    {
        return [
            'reviewer' => ['reviewer', DeveloperResource::class],
            'pull_request' => ['pullRequest', PullRequestResource::class],
        ];
    }
}
