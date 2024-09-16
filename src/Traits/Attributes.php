<?php

namespace Brainstud\JsonApi\Traits;

trait Attributes
{
    /**
     * Get the attributes for the resource
     *
     * If the resource is defined via a `register` method, this wile use
     * registration data. Else return the value of `toAttributes`.
     */
    private function getAttributes($request): array
    {
        if ($this->isRegistered()) {
            return $this->getRegisteredAttributes($request);
        }

        return $this->toAttributes($request);
    }

    private function getRegisteredAttributes($request): array
    {
        $attributes = $this->registrationData['attributes'];
        $type = $this->registrationData['type'];

        if (! ($fieldSet = $request->query('fields'))
            || ! array_key_exists($type, $fieldSet)
            || ! ($fields = explode(',', $fieldSet[$type]))
        ) {
            return $attributes;
        }

        return array_filter($attributes, fn ($key) => in_array($key, $fields), ARRAY_FILTER_USE_KEY);
    }
}
