<?php

namespace Brainstud\JsonApi\Resources;

use Brainstud\JsonApi\Traits;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

/**
 * @mixin Attributes
 */
abstract class JsonApiResource extends JsonResource
{
    use Traits\Attributes;
    use Traits\Links;
    use Traits\Meta;
    use Traits\Relationships;

    /**
     * The registered resource data.
     */
    private array $registrationData;

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
     * The way the resource is created. register|toArray.
     */
    private string $creationType;

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
        $this->registrationData = $this->register();
        $this->resourceKey = "{$this->getType()}.{$this->getId()}";
        $this->creationType = empty($this->registrationData) ? 'toArray' : 'register';

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

        $response = Arr::where([
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
     * Default to either `registrationData['id']` or an
     * `identifier` field on the resource.
     */
    protected function getId(): string
    {
        return $this->registrationData['id'] ?? $this->resource->identifier;
    }

    /**
     * Get the id of the resource.
     *
     * Default to either `registrationData['type']` or a
     * `type` field on the resource.
     */
    protected function getType(): string
    {
        return $this->registrationData['type'] ?? $this->type;
    }

    /**
     * Define the attributes for the resource.
     *
     * Default to either `registrationData['attributes']` or an empty array.
     * Should be overwritten to create custom attributes.
     */
    protected function toAttributes(Request $request): array
    {
        return $this->registrationData['attributes'] ?? [];
    }

    /**
     * Define the relationships for the resource.
     *
     * Default to either `registrationData['relationships']` or an empty array.
     * Should be overwritten to create custom relationships.
     */
    protected function toRelationships(Request $request): array
    {
        return $this->registrationData['relationships'] ?? [];
    }

    /**
     * Define the metadata for the resource.
     *
     * Default to either `registrationData['meta']` or an empty array.
     * Should be overwritten to create custom metadata.
     */
    protected function toMeta(Request $request): array
    {
        return $this->registrationData['meta'] ?? [];
    }

    /**
     * Define the links for the resource.
     *
     * Default to either `registrationData['links']` or an empty array.
     * Should be overwritten to create custom links.
     */
    protected function toLinks(Request $request): array
    {
        return $this->registrationData['links'] ?? [];
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

    /**
     * Check if the resource is created via the `register()` method.
     */
    public function isRegistered(): bool
    {
        return $this->creationType === 'register';
    }
}
