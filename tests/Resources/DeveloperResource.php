<?php

namespace Brainstud\JsonApi\Tests\Resources;

use Brainstud\JsonApi\Resources\JsonApiResource;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class DeveloperResource extends JsonApiResource
{
    protected string $type = 'developers';

    protected function toAttributes(Request $request): array
    {
        $data = [
            'name' => $this->resource->name,
        ];

        if (isset($this->resource->email)) {
            $data['email'] = $this->resource->email;
        }

        return $data;
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
        return Arr::whereNotNull([
            'experienced_developer' => $this->when(
                $this->resource->pullRequests()->count() >= 10,
                true,
                null
            ),
        ]);
    }
}
