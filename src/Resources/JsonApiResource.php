<?php

namespace Brainstud\JsonApi\Resources;

use Brainstud\JsonApi\Traits;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class JsonApiResource extends JsonResource
{
    use Traits\Attributes;
    use Traits\Id;
    use Traits\Links;
    use Traits\Meta;
    use Traits\Relationships;

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
     * Resolve the resource data to an array.
     *
     * Override Laravel 12's implementation to prevent circular dependency
     * between toArray() and toAttributes().
     */
    public function resolveResourceData(Request $request): array
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
     * Define the `id` for the resource.
     *
     * Defaults to return null.
     * Should be overwritten to use a custom `id`.
     *
     * __NOTE__: If this method is not overwritten:
     * The package will try to guess the `id` by calling the Eloquent method
     * `getRouteKeyName`, an `id` property or return `null`.
     */
    protected function toId(): string|int|null
    {
        return null;
    }

    /**
     * Get the type of the resource.
     *
     * Defaults to the `$type` property on the resource class.
     * Should be overwritten or set as a property.
     */
    protected function getType(): string
    {
        return $this->type;
    }

    /**
     * Transform the resource into an array of attributes for JSON:API.
     *
     * This method is overridden from Laravel's JsonResource to provide
     * JSON:API-specific behavior. In JSON:API, attributes are just one part
     * of the resource (alongside id, type, relationships, etc.).
     *
     * Unlike Laravel's default implementation which returns the full resource,
     * this should return ONLY the attributes portion.
     */
    public function toAttributes(Request $request): array
    {
        return [];
    }

    /**
     * Define the relationships for the resource.
     *
     * Defaults to an empty array.
     * Should be overwritten to create custom relationships.
     */
    protected function toRelationships(Request $request): array
    {
        return [];
    }

    /**
     * Define the metadata for the resource.
     *
     * Defaults to an empty array.
     * Should be overwritten to create custom metadata.
     */
    protected function toMeta(Request $request): array
    {
        return [];
    }

    /**
     * Define the links for the resource.
     *
     * Defaults to an empty array.
     * Should be overwritten to create custom links.
     */
    protected function toLinks(Request $request): array
    {
        return [];
    }
}
