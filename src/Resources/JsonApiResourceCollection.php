<?php

namespace Brainstud\JsonApi\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;

class JsonApiResourceCollection extends ResourceCollection
{
    /**
     * Build the response
     *
     * @param  Request  $request
     */
    public function toArray($request): array
    {
        $response = [
            'data' => $this->collection,
        ];

        return $this->addToResponse($request, $response);
    }

    /**
     * Hook into the generated response and optionally manipulate it.
     *
     * @param  Request  $request
     */
    protected function addToResponse($request, array $response): array
    {
        return $response;
    }

    /**
     * Include the loaded relations
     *
     * @param  Request  $request
     */
    public function with($request): array
    {
        $with = [];
        $includes = $this->composeIncludesForCollection();
        if ($includes->isNotEmpty()) {
            $with['included'] = $includes->values()->all();
        }

        return $with;
    }

    /**
     * Add metadata to each resource of the collection.
     *
     * Performs a callback on the collection that adds the output of the given callback to the meta of that resource.
     * You have acces to the model (resource) in the callback.
     *
     * i.e.
     * `(new ObjectResourceCollection)($data)->addMetaToResource(fn (Model $objectModel) => ["meta" => $objectModel->getMeta()])`
     */
    public function addMetaToResources(callable $fn): self
    {
        $this->collection->each(fn (JsonApiResource $resource) => $resource->addMeta($fn($resource->resource)));

        return $this;
    }

    /**
     * Compose a unique collection of loaded relations
     */
    private function composeIncludesForCollection(): Collection
    {
        $includes = new Collection;
        foreach ($this->collection as $singleResource) {
            if ($singleResource->getIncludedResources()->isNotEmpty()) {
                $includes = $includes->merge($singleResource->getIncludedResources());
            }
        }

        return $includes->unique('resourceKey');
    }
}