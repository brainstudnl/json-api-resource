<?php

namespace Brainstud\JsonApi\Resources;

use Brainstud\JsonApi\Traits;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class JsonApiResource extends JsonResource
{
    use Traits\Attributes;
    use Traits\Links;
    use Traits\Meta;
    use Traits\Relationships;

    /**
     * The registered resource data.
     *
     * @deprecated
     */
    private array $registerData;

    /**
     * The resource response data.
     */
    private array $data;

    /**
     * The unique key of this resource.
     */
    public string $resourceKey;

    /**
     * The max relationship depth of this resource.
     *
     * @var int|mixed
     */
    public int $resourceDepth = 0;

    /**
     * The maximum amount of (sub) includes to include.
     */
    private int $maxResourceDepth;

    /**
     * Construct with either a resource or an array with a resource and resource depth.
     */
    public function __construct($jsonApiResourceData)
    {
        $resource = $jsonApiResourceData;

        if (is_array($jsonApiResourceData)) {
            [$resource, $maxResourceDepth, $resourceDepth] = array_pad($jsonApiResourceData, 3, null);
        }

        parent::__construct($resource);

        $this->maxResourceDepth = $maxResourceDepth ?? 2;
        $this->resourceDepth = $resourceDepth ?? 0;
        $this->registerData = $this->register();
        $this->resourceKey = "{$this->getType()}.{$this->getId()}";
    }

    /**
     * Build the response.
     *
     * @param  Request  $request
     * @return array The response
     */
    public function toArray($request): array
    {
        return is_null($this->resource)
            ? []
            : $this->addToResponse($request, $this->getResourceData($request));
    }

    /**
     * Returns the value of $this->data and sets it if it's empty.
     */
    public function getResourceData($request): array
    {
        if (empty($this->data)) {
            $this->data = array_filter(
                [
                    'id' => $this->getId(),
                    'type' => $this->getType(),
                    'attributes' => $this->getAttributes($request),
                    'relationships' => empty($this->relationshipReferences) ? $this->resolveRelationships($request) : $this->relationshipReferences,
                    'meta' => $this->getMeta($request),
                    'links' => $this->getLinks($request),
                ],
                fn ($value, $key) => $key === 'attributes' || ! empty($value),
                ARRAY_FILTER_USE_BOTH
            );
        }

        return $this->data;
    }

    /**
     * Merge with another resource.
     */
    private function mergeWith(?JsonApiResource $second = null): JsonApiResource
    {
        if (! $second) {
            return $this;
        }

        $this->data = array_replace_recursive(
            $this->filter($this->getResourceData($this->request)),
            $this->filter($second->getResourceData($this->request)),
        );

        return $this;
    }

    /**
     * Include the loaded relations.
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

    /**
     * Get the id of the resource.
     *
     * Default to either `registerData['id']` or an
     * `identifier` field on the resource.
     */
    protected function getId(): string
    {
        return $this->registerData['id'] ?? $this->resource->{$this->resource->getRouteKeyName()};
    }

    /**
     * Get the type of the resource.
     *
     * Default to either `registerData['type']` or a
     * `type` field on the resource.
     */
    protected function getType(): string
    {
        return $this->registerData['type'] ?? $this->type;
    }

    /**
     * Define the attributes for the resource.
     *
     * Default to either `registerData['attributes']` or an empty array.
     * Should be overwritten to create custom attributes.
     */
    protected function toAttributes(Request $request): array
    {
        return $this->registerData['attributes'] ?? [];
    }

    /**
     * Define the relationships for the resource.
     *
     * Default to either `registerData['relationships']` or an empty array.
     * Should be overwritten to create custom relationships.
     */
    protected function toRelationships(Request $request): array
    {
        return $this->registerData['relationships'] ?? [];
    }

    /**
     * Define the metadata for the resource.
     *
     * Default to either `registerData['meta']` or an empty array.
     * Should be overwritten to create custom metadata.
     */
    protected function toMeta(Request $request): array
    {
        return $this->registerData['meta'] ?? [];
    }

    /**
     * Define the links for the resource.
     *
     * Default to either `registerData['links']` or an empty array.
     * Should be overwritten to create custom links.
     */
    protected function toLinks(Request $request): array
    {
        return $this->registerData['links'] ?? [];
    }

    /**
     * Register the resource definition.
     *
     * @deprecated Use method based resource definitions instead.
     */
    protected function register(): array
    {
        return [];
    }
}
