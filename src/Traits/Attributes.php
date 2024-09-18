<?php

namespace Brainstud\JsonApi\Traits;

use Illuminate\Http\Request;

trait Attributes
{
    /**
     * Get the attributes for the resource.
     *
     * If the resource is defined via a `register` method, this wile use
     * registration data. Else return the value of `toAttributes`.
     */
    private function getAttributes($request): array
    {
        return $this->getFilteredAttributes(
            $request,
            $this->toAttributes($request),
            $this->getType(),
        );
    }

    /**
     * Get filtered attributes.
     *
     * This method also takes an optional query parameter `fields` into account.
     * If the parameter is set, it only returns those fields in the attributes.
     */
    private function getFilteredAttributes(
        Request $request,
        array $attributes,
        string $type): array
    {
        if (! ($fieldSet = $request->query('fields'))
            || ! array_key_exists($type, $fieldSet)
            || ! ($fields = explode(',', $fieldSet[$type]))
        ) {
            return $attributes;
        }

        return $this->filterAttributes($attributes, $fields);
    }

    /**
     * Filter the attributes to only include given fields.
     *
     * This method cleans the retrieved attributes by calling the `$this->filter()`
     * method. This will flatten the `MergeValue` and `MissingValue` entries.
     *
     * @see JsonResource `->filter()`
     */
    private function filterAttributes(array $attributes, array $fields): array
    {
        return array_filter(
            $this->filter($attributes),
            fn ($key) => in_array($key, $fields),
            ARRAY_FILTER_USE_KEY
        );
    }
}
