<?php

namespace Brainstud\JsonApi\Traits;

trait Meta
{
    /**
     * Metadata for the resource
     *
     * NOTE: this differs from `additional` on JsonResource which adds to the response.
     */
    public array $meta = [];

    /**
     * Get the metadata for the resource.
     */
    private function getMeta($request): array
    {
        return array_merge($this->toMeta($request), $this->meta);
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
}
