<?php

namespace Brainstud\JsonApi\Traits;

trait Id
{
    private function getId(): int|string|null
    {
        return $this->toId() ?? $this->guessId();
    }

    /**
     * Try and guess the id for the resource.
     *
     * This method tries to guess the id for the resource. It does so by first
     * trying the `id` field on the `registrationData`. Then, it checks if
     * the method `getRouteKeyBinding` exists on the resource, if so, it
     * uses that method to retrieve the identifier. If not, it tries to
     * just use the `id` property on the resource and returns `null`
     * otherwise.
     */
    private function guessId(): int|string|null
    {
        return $this->idFromRegisterData()
            ?? $this->fromRouteKey()
            ?? $this->fromIdAttribute()
            ?? null;
    }

    private function idFromRegisterData(): int|string|null
    {
        return $this->registerData['id'] ?? null;
    }

    private function fromRouteKey(): int|string|null
    {
        return method_exists($this->resource, 'getRouteKeyName')
            ? $this->resource->{$this->resource->getRouteKeyName()}
            : null;
    }

    private function fromIdAttribute(): int|string|null
    {
        return property_exists($this->resource, 'id')
            ? $this->resource->id
            : null;
    }
}
