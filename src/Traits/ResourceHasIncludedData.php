<?php


namespace Brainstud\Packages\JsonApi\Traits;


use Brainstud\Packages\JsonApi\Helpers\JsonApiCollectionResource;

trait ResourceHasIncludedData
{
    public $includedData = [];

    public function addInclude($object): self
    {
        if (is_subclass_of($object,JsonApiCollectionResource::class)) {
            foreach ($object->collection as $item) {
                $item->isRelationshipReference = false;
                $this->includedData[] = $item;
            }
        } else {
            $this->includedData[] = $object;
        }

        return $this;
    }

    public function addIncludes(array $objects): self
    {
        foreach ($objects as $object) {
            $this->addInclude($object);
        }
        return $this;
    }

    public function getWithIncluded(): array
    {
        if (! empty($this->includedData)) {
            return [
                'included' => $this->includedData
            ];
        }

        return [];
    }
}
