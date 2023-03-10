<?php

namespace Brainstud\JsonApi\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

abstract class JsonApiResource extends JsonResource
{
    /**
     * The object represented in this resource
     * @var mixed
     */
    protected mixed $resourceObject;


    /**
     * Prevent specific properties from the model to be published in the resource.
     * @var $except string[]
     */
    protected array $except;

    /**
     * Select specific attributes from the model of the resource.
     * @var $only string[]
     */
    protected array $only;

    /**
     * Overwrite model key where the model is identified by within the resource (e.g. `identifier` or `id`).
     * @var string $identifiedBy
     */
    protected string $identifiedBy;

    /**
     * The registered resource data
     * @var array
     */
    private array $resourceRegistrationData;

    /**
     * The relation references of this resource
     * @var array
     */
    private array $resourceRelationshipReferences = [];

    /**
     * The included resources that the relation references are referencing
     * @var array
     */
    public array $included = [];

    /**
     * The unique key of this resource
     * @var string
     */
    public string $resourceKey;

    /**
     * The max relationship depth of this resource
     * @var int|mixed
     */
    public int $resourceDepth = 0;

    private int $maxResourceDepth;

    /**
     * Construct with either a resource or an array with a resource and resource depth
     * @param $jsonApiResourceData
     */
    public function __construct($jsonApiResourceData)
    {
        $resource = $jsonApiResourceData;

        if (is_array($jsonApiResourceData)) {
            list($resource, $maxResourceDepth, $resourceDepth) = array_pad($jsonApiResourceData, 3, null);
        }

        $this->maxResourceDepth = $maxResourceDepth ?? 2;

        parent::__construct($resource);

        $this->resourceDepth =  $resourceDepth ?? 0;
        $this->resourceObject = $resource;
        $this->resourceRegistrationData = $this->register();
        $this->resourceKey = "{$this->getType()}.{$this->getId()}";

        if ($this->resourceDepth < $this->maxResourceDepth) {
            $this->mapRelationships();
        }

        if ($this->resourceDepth < ($this->maxResourceDepth - 1)) {
            $this->addSubIncludes();
        }
    }

    /**
     * Register the resource definition
     * @return array
     */
    abstract protected function register(): array;

    /**
     * toId.
     *
     * When string is returned, it will be set as the JSON:API `id` property.
     * @return string|null
     */
    protected function toId(): null | string {
        return null;
    }

    /**
     * getId.
     *
     * Returns the id of the resource.
     * @return string
     */
    protected function getId(): string {
        return (
            $this->toId()
            ?? $this->resourceRegistrationData['id']
            ?? $this->resourceObject->{$this->identifiedBy ?? $this->resourceObject->getRouteKeyName()}
        );
    }

    /**
     * toType.
     *
     * When string is returned, it will be set as the JSON:API `type` property.
     * @return string|null
     */
    protected function toType(): null | string {
        return null;
    }

    /**
     * getType.
     *
     * Returns the type of the resource.
     * @return string
     */
    protected function getType(){
        return (
            $this->toType() ??
            $this->resourceRegistrationData['type'] ??
            Str::snake(Str::plural(class_basename($this->resourceObject)))
        );
    }


    /**
     * Build the response
     * @param Request $request
     * @return array The response
     */
    public function toArray($request): array
    {
        if (is_null($this->resourceObject)) {
            return [];
        }

        $response = [
            'id' => $this->resourceObject->{$this->identifiedBy ?? $this->resourceObject->getRouteKeyName()},
            'type' => $this->getType(),
            'attributes' => $this->getAttributes($request),
        ];

        if (!empty($this->resourceRegistrationData['meta'])) {
            $response['meta'] = $this->resourceRegistrationData['meta'];
        }

        if (!empty($this->resourceRegistrationData['links'])) {
            $response['links'] = $this->resourceRegistrationData['links'];
        }

        if (!empty($this->resourceRelationshipReferences)) {
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
     * @param $relationKey
     * @param $relationData
     */
    private function mapSingleRelationship($relationKey, $relationData): void
    {
        list($resourceData, $resourceClass) = $relationData;

        // The data can be a string reference to relation name on the model or a resource model itself
        $resourceData = is_string($resourceData)
            ? $this->convertRelationStringToReference($resourceData)
            : $resourceData;

        if (!$this->resourceHasData($resourceData, $resourceClass)) {
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
     * @param $relationKey
     * @param $resourceDataCollection
     * @param $resourceCollectionClass
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
            if (!$includedResource instanceof self) {
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
     * @param $relationKey
     * @param $resourceData
     * @param $resourceClass
     */
    private function addResourceRelation($relationKey, $resourceData, $resourceClass): void
    {
        $includedResource = new $resourceClass([$resourceData, $this->maxResourceDepth, $this->resourceDepth + 1]);
        if (!$includedResource instanceof self) {
            return;
        }

        $this->addInclude($includedResource);

        $this->resourceRelationshipReferences[$relationKey] = [
            'data' => $includedResource->toRelationshipReferenceArray(),
        ];
    }

    /**
     * Check if the resource object has data
     * @param $resourceData
     * @param $resourceClass
     * @return bool
     */
    private function resourceHasData($resourceData, $resourceClass): bool
    {
        if ($resourceData === null) {
            return false;
        }

        if (is_subclass_of($resourceClass, JsonApiCollectionResource::class) &&
            $resourceData->isEmpty()
        ) {
            return false;
        }

        return true;
    }

    /**
     * Return the relation if it's loaded on the model
     * @param string $dataPath The method name of the relation
     * @return mixed The loaded relationship
     */
    private function convertRelationStringToReference(string $dataPath): mixed
    {
        if (method_exists($this->resourceObject, 'relationLoaded')
            && $this->resourceObject->relationLoaded($dataPath) === false
        ) {
            return null;
        } elseif (!isset($this->resourceObject->{$dataPath})) {
            return null;
        }

        return $this->resourceObject->{$dataPath};
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
     * @param JsonApiResource $includedResource
     * @return $this
     */
    private function addInclude(JsonApiResource $includedResource): self {
        $existingIncludeResource = ($this->included[$includedResource->resourceKey]) ?? null;
        $this->included[$includedResource->resourceKey] = $includedResource->combine($existingIncludeResource);
        return $this;
    }

    /**
     * combine.
     *
     * Merges two similar resources together.
     *
     * TODO: Make sure this combine method works with dynamic attributes (`only`, `except`, `toAttributes`).
     *
     * @param JsonApiResource|null $second
     * @return JsonApiResource
     */
    private function combine(JsonApiResource $second = null): JsonApiResource {
        if ( ! $second ) {
            return $this;
        }
        $this->resourceRegistrationData = array_replace_recursive(
            $this->resourceRegistrationData,
            $second->resourceRegistrationData,
        );
        return $this;
    }

    /**
     * getIncludedResources.
     *
     * @return Collection A collection of the includes.
     */
    public function getIncludedResources(): Collection {
        return collect(array_values($this->included));
    }

    /**
     * toAttributes.
     *
     * When an array is returned, it will set the attributes of the resource to that array.
     *
     * @param Request $request
     * @param $model
     * @return array|null
     */
    protected function toAttributes(Request $request, $model): ?array {
        return null;
    }

    /**
     * getAttributes.
     *
     * Assembles the attributes as requested, based on the information provided.
     *
     * @param Request $request
     * @return mixed
     */
    private function getAttributes(Request $request)
    {
        if(
            array_key_exists('attributes', $this->resourceRegistrationData)
            || $this->toAttributes($request, $this->resourceObject) !== null
        ){
            $attributes = array_merge(
                $this->resourceRegistrationData['attributes'] ?? [],
                $this->toAttributes($request, $this->resourceObject) ?? [],
            );
        }else{
            $keys = $this->only ?? (
                array_filter(
                    $this->resourceObject->getFillable(),
                    fn($key) => !in_array($key, ['identifier', ...($this->except ?? [])])
                )
            );

            $attributes = array_filter(
                $this->resourceObject->getAttributes(),
                fn($key) => (
                    !!$this->resourceObject->{$key}
                    && in_array($key, $keys)
                ),
                ARRAY_FILTER_USE_KEY
            );
        }
        $type = $this->getType();
        if (
            ($fieldSet = $request->query('fields'))
            && array_key_exists($type, $fieldSet)
            && ($fields = explode(',', $fieldSet[$type]))
        ) {
            $attributes = array_filter($attributes, fn($key) => in_array($key, $fields), ARRAY_FILTER_USE_KEY);
        }

        return array_map(fn ($attribute) => (
            is_callable($attribute)
                ? $attribute($request, $this->resourceObject)
                : $attribute
        ), $attributes);

    }

    /**
     * Create a relationship reference
     * @return array
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
     * @param Request $request
     * @return array
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
     * @param Request $request
     * @param array $response
     * @return array
     */
    protected function addToResponse($request, array $response): array
    {
        return $response;
    }
}
