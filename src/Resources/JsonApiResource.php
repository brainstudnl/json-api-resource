<?php

namespace Brainstud\JsonApi\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

abstract class JsonApiResource extends JsonResource
{
    /**
     * The registered resource data
     */
    private array $resourceRegistrationData;

    /**
     * The relation references of this resource
     */
    private array $resourceRelationshipReferences = [];

    /**
     * The included resources that the relation references are referencing
     */
    public array $included = [];

    /**
     * The unique key of this resource
     */
    public string $resourceKey;

    /**
     * The max relationship depth of this resource
     *
     * @var int|mixed
     */
    public int $resourceDepth = 0;

    private int $maxResourceDepth;

    /**
     * Construct with either a resource or an array with a resource and resource depth
     */
    public function __construct($jsonApiResourceData)
    {
        $resource = $jsonApiResourceData;

        if (is_array($jsonApiResourceData)) {
            [$resource, $maxResourceDepth, $resourceDepth] = array_pad($jsonApiResourceData, 3, null);
        }

        $this->maxResourceDepth = $maxResourceDepth ?? 2;

        parent::__construct($resource);

        $this->resourceDepth = $resourceDepth ?? 0;
        $this->resourceRegistrationData = $this->register();
        $this->resourceKey = "{$this->resourceRegistrationData['type']}.{$this->resourceRegistrationData['id']}";

        if ($this->resourceDepth < $this->maxResourceDepth) {
            $this->mapRelationships();
        }

        if ($this->resourceDepth < ($this->maxResourceDepth - 1)) {
            $this->addSubIncludes();
        }
    }

    /**
     * Register the resource definition
     */
    abstract protected function register(): array;

    /**
     * Build the response
     *
     * @param  Request  $request
     * @return array The response
     */
    public function toArray($request): array
    {
        if (is_null($this->resource)) {
            return [];
        }

        $response = [
            'id' => $this->resourceRegistrationData['id'],
            'type' => $this->resourceRegistrationData['type'],
            'attributes' => $this->getAttributes($request),
        ];

        if (! empty($this->resourceRegistrationData['meta'])) {
            $response['meta'] = $this->resourceRegistrationData['meta'];
        }

        if (! empty($this->resourceRegistrationData['links'])) {
            $response['links'] = $this->resourceRegistrationData['links'];
        }

        if (! empty($this->resourceRelationshipReferences)) {
            $response['relationships'] = $this->resourceRelationshipReferences;
        }

        return $this->addToResponse($request, $response);
    }

    /**
     * Map all registered relationships to a resource
     */
    private function mapRelationships(): void
    {
        if (empty($this->resourceRegistrationData['relationships'])) {
            return;
        }

        foreach ($this->resourceRegistrationData['relationships'] as $relationKey => $relationData) {
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
        if (is_subclass_of($resourceClass, JsonApiCollectionResource::class)) {
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

        $this->resourceRelationshipReferences[$relationKey] = [
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

        $this->resourceRelationshipReferences[$relationKey] = [
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

        if (
            is_subclass_of($resourceClass, JsonApiCollectionResource::class) &&
            $resourceData->isEmpty()
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
        $this->resourceRegistrationData = array_replace_recursive(
            $this->resourceRegistrationData,
            $second->resourceRegistrationData,
        );

        $this->resourceRelationshipReferences = array_replace_recursive(
            $this->resourceRelationshipReferences,
            $second->resourceRelationshipReferences,
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
     * getAttributes.
     *
     * Assembles the attributes as requested, based on the information provided.
     *
     * @return mixed
     */
    private function getAttributes(Request $request): array
    {
        $attributes = $this->resourceRegistrationData['attributes'];
        $type = $this->resourceRegistrationData['type'];

        if (
            ! ($fieldSet = $request->query('fields'))
            || ! array_key_exists($type, $fieldSet)
            || ! ($fields = explode(',', $fieldSet[$type]))
        ) {
            return $attributes;
        }

        return array_filter($attributes, fn ($key) => in_array($key, $fields), ARRAY_FILTER_USE_KEY);
    }

    /**
     * Create a relationship reference
     */
    public function toRelationshipReferenceArray(): array
    {
        return [
            'id' => $this->resourceRegistrationData['id'],
            'type' => $this->resourceRegistrationData['type'],
        ];
    }

    /**
     * Include the loaded relations
     *
     * @param  Request  $request
     */
    public function with($request): array
    {
        $with = [];
        if ($this->getIncludedResources()->isNotEmpty()) {
            $with['included'] = $this->getIncludedResources();
        }

        return $with;
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
}
