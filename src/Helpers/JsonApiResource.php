<?php

namespace Brainstud\Packages\JsonApi\Helpers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

abstract class JsonApiResource extends JsonResource
{
    /** @var mixed */
    protected $resourceObject;

    /** @var array */
    private $resourceRegistrationData;

    /** @var array */
    private $resourceRelationshipReferences = [];

    /** @var Collection */
    public $includedResources;

    /** @var string */
    public $resourceKey;

    /** @var int|mixed  */
    public $resourceDepth = 0;

    /**
     * Construct with either a resource or an array with a resource and resource depth
     * @param $jsonApiResourceData
     */
    public function __construct($jsonApiResourceData)
    {
        $resource = $jsonApiResourceData;
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

        if ($this->resourceDepth < 2) {
            $this->mapRelationships();
        }

        if ($this->resourceDepth === 0) {
            $this->addSubIncludes();
        }
    }

    public static function fromRelation($resource, $resourceDepth = 1): self
    {
        return new static([$resource, $resourceDepth]);
    }

    private function mapRelationships(): void
    {
        if (empty($this->resourceRegistrationData['relationships'])) {
            return;
        }

        foreach ($this->resourceRegistrationData['relationships'] as $relationKey => $relationData) {
            $this->mapSingleRelationship($relationKey, $relationData);
        }
    }

    private function mapSingleRelationship($relationKey, $relationData): void
    {
        list($resourceData, $resourceClass) = $relationData;

        $resourceData = is_string($resourceData)
            ? $this->convertRelationStringToReference($resourceData)
            : $resourceData;

        if (! $this->resourceHasData($resourceData, $resourceClass)) {
            return;
        }

        if (is_subclass_of($resourceClass, JsonApiCollectionResource::class)) {
            $this->addResourceCollectionRelation($relationKey, $resourceData, $resourceClass);
            return;
        }

        $this->addResourceRelation($relationKey, $resourceData, $resourceClass);
    }

    private function addResourceCollectionRelation($relationKey, $resourceDataCollection, $resourceCollectionClass): void
    {
        $resourceClass = (new $resourceCollectionClass([]))->collects;
        $relationshipReferences = [];

        foreach($resourceDataCollection as $resourceData) {
            $includedResource = $resourceClass::fromRelation($resourceData, $this->resourceDepth + 1);
            $this->includedResources->push($includedResource);
            $relationshipReferences[] = $includedResource->toRelationshipReferenceArray();
        }

        $this->resourceRelationshipReferences[$relationKey] = [
            'data' => $relationshipReferences,
        ];
    }

    private function addResourceRelation($relationKey, $resourceData, $resourceClass): void
    {
        $includedResource = $resourceClass::fromRelation($resourceData, $this->resourceDepth + 1);
        $this->includedResources->push($includedResource);
        $this->resourceRelationshipReferences[$relationKey] = [
            'data' => $includedResource->toRelationshipReferenceArray(),
        ];
    }

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

    private function convertRelationStringToReference(string $dataPath)
    {
        if ($this->resourceObject->relationLoaded($dataPath) === false) {
            return null;
        }

        return $this->resourceObject->{$dataPath};
    }

    private function addSubIncludes(): void
    {
        foreach ($this->includedResources as $subResource) {
            if ($subResource->includedResources->isNotEmpty()) {
                $this->includedResources = $this->includedResources->merge($subResource->includedResources);
            }
        }
    }

    public function toArray($request): array
    {
        if (is_null($this->resourceObject)) {
            return [];
        }

        $response = [
            'id' => $this->resourceRegistrationData['id'],
            'type' => $this->resourceRegistrationData['type'],
            'attributes' => $this->resourceRegistrationData['attributes'],
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

        return $response;
    }

    public function toRelationshipReferenceArray(): array
    {
        return [
            'id' => $this->resourceRegistrationData['id'],
            'type' => $this->resourceRegistrationData['type'],
        ];
    }

    public function with($request): array
    {
        $with = [];
        if ($this->includedResources->isNotEmpty()) {
            $with['included'] = $this->includedResources;
        }

        return $with;
    }
}
