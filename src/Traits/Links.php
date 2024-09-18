<?php

namespace Brainstud\JsonApi\Traits;

trait Links
{
    /**
     * Get the links for the resource.
     */
    private function getLinks($request): array
    {
        return $this->filter($this->toLinks($request));
    }
}
