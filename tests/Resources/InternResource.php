<?php

namespace Brainstud\JsonApi\Tests\Resources;

use Brainstud\JsonApi\Resources\JsonApiResource;
use Brainstud\JsonApi\Tests\Models\Intern;
use Illuminate\Http\Request;

/**
 * @property Intern $resource
 */
class InternResource extends JsonApiResource
{
    protected string $type = 'interns';

    protected function toAttributes(Request $request): array
    {
        return [
            'name' => $this->resource->name,
            'department' => $this->resource->department,
        ];
    }
}
