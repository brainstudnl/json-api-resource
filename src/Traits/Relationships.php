<?php

namespace Brainstud\JsonApi\Traits;

use Brainstud\JsonApi\Resources\JsonApiResource;
use Brainstud\JsonApi\Resources\JsonApiResourceCollection;
use Illuminate\Support\Collection;

trait Relationships
{
    /**
     * The relation references of this resource
     */
    private array $relationshipReferences = [];

    /**
     * The included resources that the relation references are referencing
     */
    public array $included = [];

    /**
     * Get the relationships for the resource.
     */
    private function getRelationships($request): array
    {
        return $this->isRegistered()
            ? $this->relationshipReferences
            : $this->processRelationships($this->toRelationships($request));
    }

    public function processRelationships(array $relationships): array
    {
        if ($this->resourceDepth < $this->maxResourceDepth) {
            $this->mapRelationships($relationships);
        }

        if ($this->resourceDepth < ($this->maxResourceDepth - 1)) {
            $this->addSubIncludes();
        }

        return $this->relationshipReferences ?? [];
    }

    /**
     * Map all registered relationships to a resource
     */
    private function mapRelationships(array $relationships): void
    {
        if (empty($relationships)) {
            return;
        }

        foreach ($relationships as $relationKey => $relationData) {
            $this->mapSingleRelationship($relationKey, $relationData);
        }
    }

    /**
     * Map a single relationship into an included resource
     */
    private function mapSingleRelationship($relationKey, $relationData): void
    {
        [$resourceData, $resourceClass] = $relationData;

        // The data can be a string reference to relation name on the model or a resource model itself
        $resourceData = is_string($resourceData)
            ? $this->convertRelationStringToReference($resourceData)
            : $resourceData;

        if (! $this->resourceHasData($resourceData, $resourceClass)) {
            return;
        }

        // Is this relation a collection or a single resource
        if (is_subclass_of($resourceClass, JsonApiResourceCollection::class)) {
            $this->addResourceCollectionRelation($relationKey, $resourceData, $resourceClass);

            return;
        }

        $this->addResourceRelation($relationKey, $resourceData, $resourceClass);
    }

    /**
     * Add a collection relationship to the resource relationships and included data
     */
    private function addResourceCollectionRelation(
        $relationKey,
        $resourceDataCollection,
        $resourceCollectionClass
    ): void {
        $resourceClass = (new $resourceCollectionClass([]))->collects;
        $relationshipReferences = [];

        foreach ($resourceDataCollection as $resourceData) {
            $includedResource = new $resourceClass([$resourceData, $this->maxResourceDepth, $this->resourceDepth + 1]);
            if (! $includedResource instanceof self) {
                continue;
            }
            $this->addInclude($includedResource);
            $relationshipReferences[] = $includedResource->toRelationshipReferenceArray();
        }

        $this->relationshipReferences[$relationKey] = [
            'data' => $relationshipReferences,
        ];
    }

    /**
     * Add a relation to the resource
     */
    private function addResourceRelation($relationKey, $resourceData, $resourceClass): void
    {
        $includedResource = new $resourceClass([$resourceData, $this->maxResourceDepth, $this->resourceDepth + 1]);
        if (! $includedResource instanceof self) {
            return;
        }

        $this->addInclude($includedResource);

        $this->relationshipReferences[$relationKey] = [
            'data' => $includedResource->toRelationshipReferenceArray(),
        ];
    }

    /**
     * Check if the resource object has data
     */
    private function resourceHasData($resourceData, $resourceClass): bool
    {
        if ($resourceData === null) {
            return false;
        }

        if (is_subclass_of($resourceClass, JsonApiResourceCollection::class)
            && $resourceData->isEmpty()
        ) {
            return false;
        }

        return true;
    }

    /**
     * Return the relation if it's loaded on the model
     *
     * @param  string  $dataPath  The method name of the relation
     * @return mixed The loaded relationship
     */
    private function convertRelationStringToReference(string $dataPath): mixed
    {
        if (
            method_exists($this->resource, 'relationLoaded')
            && $this->resource->relationLoaded($dataPath) === false
        ) {
            return null;
        } elseif (! isset($this->resource->{$dataPath})) {
            return null;
        }

        return $this->resource->{$dataPath};
    }

    /**
     * Flatten the includes of includes
     */
    private function addSubIncludes(): void
    {
        $this->getIncludedResources()->each(
            fn ($include) => $include->getIncludedResources()->each(
                fn ($subInclude) => $this->addInclude($subInclude)
            )
        );
    }

    /**
     * addInclude.
     *
     * Add an include to the list of includes based on the resource key. That way, if an include already exists,
     * both included resources will be merged and returned as one.
     *
     * @return $this
     */
    private function addInclude(JsonApiResource $includedResource): self
    {
        $existingIncludeResource = ($this->included[$includedResource->resourceKey]) ?? null;
        $this->included[$includedResource->resourceKey] = $includedResource->combine($existingIncludeResource);

        return $this;
    }

    /**
     * combine.
     *
     * Merges two similar resources together.
     */
    private function combine(?JsonApiResource $second = null): JsonApiResource
    {
        if (! $second) {
            return $this;
        }
        $this->registrationData = array_replace_recursive(
            $this->registrationData,
            $second->registrationData,
        );

        $this->relationshipReferences = array_replace_recursive(
            $this->relationshipReferences,
            $second->relationshipReferences,
        );

        return $this;
    }

    /**
     * getIncludedResources.
     *
     * @return Collection A collection of the includes.
     */
    public function getIncludedResources(): Collection
    {
        return collect(array_values($this->included));
    }

    /**
     * Create a relationship reference
     */
    public function toRelationshipReferenceArray(): array
    {
        return [
            'id' => $this->getId(),
            'type' => $this->getType(),
        ];
    }
}
