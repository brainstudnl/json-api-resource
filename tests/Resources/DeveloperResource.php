<?php

namespace Brainstud\JsonApi\Tests\Resources;

use Brainstud\JsonApi\Resources\JsonApiResource;
use Illuminate\Http\Request;

class DeveloperResource extends JsonApiResource
{
    protected string $type = 'developers';

    protected function toAttributes(Request $request): array
    {
        return [
            'name' => $this->resource->name,
            $this->mergeWhen(isset($this->resource->email), [
                'email' => $this->resource->email,
            ]),
        ];
    }

    protected function toRelationships(Request $request): array
    {
        return [
            'pull_requests' => ['pullRequests', PullRequestResourceCollection::class],
            'reviews' => ['reviews', ReviewResourceCollection::class],
        ];
    }

    public function toMeta(Request $request): array
    {
        return array_filter([
            'experienced_developer' => $this->when(
                $this->resource->pullRequests()->count() >= 10,
                true,
                null
            ),
        ]);
    }
}
