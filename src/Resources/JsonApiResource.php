<?php

namespace Brainstud\JsonApi\Resources;

use Brainstud\JsonApi\Traits\Attributes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * @mixin Attributes
 */
abstract class JsonApiResource extends JsonResource
{
    use Attributes;

    /**
     * The registered resource data
     */
    private array $registrationData;

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

    /**
     * Metadata for the resource
     *
     * NOTE: this differs from `additional` on JsonResource which adds to the response.
     */
    public array $meta = [];

    /**
     * The maximum amount of (sub) includes to include.
     */
    private int $maxResourceDepth;

    /**
     * The way the resource is created. register|toArray.
     */
    public string $creationType;

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

        $this->registrationData = $this->register();

        // This code below is kept to allow for backwards compatability with the 'old' `->register()` method
        if ($this->registrationData !== []) {
            $this->creationType = 'register';
            $this->resourceKey = "{$this->getType()}.{$this->getId()}";
            if ($this->resourceDepth < $this->maxResourceDepth) {
                $this->mapRelationships($this->registrationData['relationships']);
            }

            if ($this->resourceDepth < ($this->maxResourceDepth - 1)) {
                $this->addSubIncludes();
            }
        } else {
            $this->creationType = 'toArray';
        }
    }

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

        $response = Arr::where([
            'id' => $this->getId(),
            'type' => $this->getType(),
            'attributes' => $this->getAttributes($request),
            'relationships' => ! empty($this->resourceRelationshipReferences) ? $this->resourceRelationshipReferences : $this->mapToRelationships($this->toRelationships($request)),
            'meta' => array_merge($this->toMeta($request), $this->meta),
            'links' => $this->toLinks($request),
        ], fn ($value) => ! empty($value));

        return $this->addToResponse($request, $response);
    }

    public function mapToRelationships(array $relationships): array
    {
        if ($this->resourceDepth < $this->maxResourceDepth) {
            $this->mapRelationships($relationships);
        }

        if ($this->resourceDepth < ($this->maxResourceDepth - 1)) {
            $this->addSubIncludes();
        }

        return $this->resourceRelationshipReferences ?? [];
    }

    /**
     * Add metadata to the resource.
     *
     * Saves the given data to the `$meta` property.
     * Please note that this metadata overwrites any added metadata from the `register()` function.
     *
     * @param  array  $data  An associative array to add to the metadata
     *
     * @throws \InvalidArgumentException if a non-associative array is given to the function
     */
    public function addMeta(array $data): self
    {
        if (! empty($data) && array_is_list($data)) {
            throw new \InvalidArgumentException('Metadata should be an associative array, i.e. ["key" => "value"]');
        }

        $this->meta = array_merge($this->meta, $data);

        return $this;
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
        ray('bonking')->trace();

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
     * Create a relationship reference
     */
    public function toRelationshipReferenceArray(): array
    {
        return [
            'id' => $this->getId(),
            'type' => $this->getType(),
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

    public function getId(): string
    {
        return $this->registrationData['id'] ?? $this->resource->{$this->identifierAttributeName};
    }

    public function getType(): string
    {
        return $this->registrationData['type'] ?? $this->type;
    }

    protected function toAttributes(Request $request): array
    {
        return $this->registrationData['attributes'] ?? [];
    }

    protected function toRelationships(Request $request): array
    {
        return $this->registrationData['relationships'] ?? [];
    }

    protected function toMeta(Request $request): array
    {
        return $this->registrationData['meta'] ?? [];
    }

    protected function toLinks(Request $request): array
    {
        return $this->registrationData['links'] ?? [];
    }

    /**
     * Register the resource definition'
     *
     * @deprecated Use method based resource definitions instead.
     */
    protected function register(): array
    {
        return [];
    }

    public function isRegistered(): bool
    {
        return $this->creationType === 'register';
    }
}
