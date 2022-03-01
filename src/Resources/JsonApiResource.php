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
    protected $resourceObject;

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
     * @var Collection
     */
    public Collection $includedResources;

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
     * @param int $maxResourceDepth
     */
    public function __construct($jsonApiResourceData, int $maxResourceDepth = 2)
    {
        $resource = $jsonApiResourceData;
        $this->maxResourceDepth = $maxResourceDepth;
        $resourceDepth = 0;

        if (is_array($jsonApiResourceData)) {
            list($resource, $resourceDepth) = $jsonApiResourceData;
        }

        parent::__construct($resource);

        $this->resourceDepth = $resourceDepth;
        $this->resourceObject = $resource;
        $this->resourceRegistrationData = $this->register();
        $this->resourceKey = "{$this->resourceRegistrationData['type']}.{$this->resourceRegistrationData['id']}";
        $this->includedResources = new Collection;

        if ($this->resourceDepth < $maxResourceDepth) {
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
            $includedResource = new $resourceClass([$resourceData, $this->resourceDepth + 1], $this->maxResourceDepth);
            if (!$includedResource instanceof self) {
                continue;
            }

            $this->includedResources->push($includedResource);
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
        $includedResource = new $resourceClass([$resourceData, $this->resourceDepth + 1], $this->maxResourceDepth);
        if (!$includedResource instanceof self) {
            return;
        }

        $this->includedResources->push($includedResource);
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
    private function convertRelationStringToReference(string $dataPath)
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
        foreach ($this->includedResources as $subResource) {
            if ($subResource->includedResources->isNotEmpty()) {
                $this->includedResources = $this->includedResources->merge($subResource->includedResources);
            }
        }
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
            'id' => $this->resourceRegistrationData['id'],
            'type' => $this->resourceRegistrationData['type'],
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
     * @param Request $request
     * @return mixed
     */
    private function getAttributes($request)
    {
        $attributes = $this->resourceRegistrationData['attributes'];
        $type = $this->resourceRegistrationData['type'];

        if (!($fieldSet = $request->query('fields'))
            || !array_key_exists($type, $fieldSet)
            || !($fields = explode(',', $fieldSet[$type]))
        ) {
            return $attributes;
        }

        return array_filter($attributes, fn ($key) => in_array($key, $fields), ARRAY_FILTER_USE_KEY);
    }

    /**
     * Create a relationship reference
     * @return array
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
     * @param Request $request
     * @return array
     */
    public function with($request): array
    {
        $with = [];
        if ($this->includedResources->isNotEmpty()) {
            $with['included'] = $this->includedResources;
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
