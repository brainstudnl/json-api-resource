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

        // We need to process the relationships on construct since we use the
        // constructor only in resolving (sub) includes. We do not call
        // the `toArray` method until the very last moment. Might be
        // an option to refactor this. But then the whole relationship
        // resolving code has to be refactored. Keeping it like this for now.
        $this->processRelationships($this->toRelationships(request()));
    }

    /**
     * Build the response.
     *
     * @param  Request  $request
     * @return array The response
     */
    public function toArray($request): array
    {
        if (is_null($this->resource)) {
            return [];
        }

        $response = array_filter([
            'id' => $this->getId(),
            'type' => $this->getType(),
            'attributes' => $this->getAttributes($request),
            'relationships' => $this->getRelationships($request),
            'meta' => $this->getMeta($request),
            'links' => $this->getLinks($request),
        ], fn ($value) => ! empty($value));

        return $this->addToResponse($request, $response);
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
        return $this->registerData['id'] ?? $this->resource->identifier;
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
