<?php

namespace Brainstud\JsonApi\Resources;

use Brainstud\JsonApi\Traits\Attributes;
use Brainstud\JsonApi\Traits\Links;
use Brainstud\JsonApi\Traits\Meta;
use Brainstud\JsonApi\Traits\Relationships;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

/**
 * @mixin Attributes
 */
abstract class JsonApiResource extends JsonResource
{
    use Attributes;
    use Links;
    use Meta;
    use Relationships;

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
            $this->processRelationships($this->registrationData['relationships']);
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
            'relationships' => $this->getRelationships($request),
            'meta' => $this->getMeta($request),
            'links' => $this->getLinks($request),
        ], fn ($value) => ! empty($value));

        return $this->addToResponse($request, $response);
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

    protected function isRegistered(): bool
    {
        return $this->creationType === 'register';
    }
}
