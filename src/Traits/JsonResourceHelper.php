<?php

namespace Brainstud\JsonApi\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * JsonResourceHelper
 *
 * Add this trait to a (test) class to add JsonResource helper to it.
 */
trait JsonResourceHelper
{
    /**
     * createJsonResource.
     *
     * Makes it easier to create an array structure of an API resource to prevent being repetitive.
     */
    protected function createJsonResource(
        Collection|Model $modelOrCollection,
        array            $relationships = null,
        array            $meta = null,
        array            $links = null,
        string           $type = null,
        array            $exceptAttributes = [],
        array            $onlyAttributes = [],
        bool             $isReferenceObject = false,
    ): array {
        $type ??= ($modelOrCollection instanceof Collection)
            ? Str::snake(Str::plural(class_basename($modelOrCollection[0])))
            : Str::snake(Str::plural(class_basename($modelOrCollection)));

        if ($modelOrCollection instanceof Collection) {
            return $modelOrCollection->map(fn ($model) => (
            $this->createJsonResource(
                $model,
                $relationships,
                type: $type,
                exceptAttributes: $exceptAttributes,
                isReferenceObject: $isReferenceObject,
            )
            ))->toArray();
        } elseif ($isReferenceObject) {
            $data = [
                'id' => $modelOrCollection->identifier,
                'type' => $type,
            ];
        } else {
            $fillableAttributes = $modelOrCollection->getFillable();
            $data = [
                'id' => $modelOrCollection->identifier,
                'type' => $type,
                'attributes' => (
                array_filter(
                    $this->getAllAttributes($modelOrCollection),
                    fn ($key) => (
                        in_array($key, $fillableAttributes)
                        && (empty($onlyAttributes) || in_array($key, $onlyAttributes))
                        && !in_array($key, ['identifier', ...$exceptAttributes])
                    ),
                    ARRAY_FILTER_USE_KEY
                )
                ),
            ];
        }

        if (!$isReferenceObject && $relationships) {
            $data['relationships'] = collect(array_keys($relationships))->reduce(function ($rels, $relationKey) use ($relationships) {
                $relation = $relationships[$relationKey];
                if ($relation instanceof Model) {
                    $rels[$relationKey] = [
                        'data' => $this->createJsonResource($relation, isReferenceObject: true),
                    ];
                } else {
                    $rels[$relationKey] = [
                        'data' => collect($relation)->map(fn ($relatedModel) => (
                        $this->createJsonResource($relatedModel, isReferenceObject: true)
                        )),
                    ];
                }

                return $rels;
            }, []);
        }

        if ($meta) {
            $data['meta'] = $meta;
        }

        if ($links) {
            $data['links'] = $links;
        }

        return $data;
    }

    /**
     * By default, laravel does not add columns where the value is null
     * to the retrieved model. Therefore, we combine the received and
     * fillable fields on the model. If a field is fillable, but not
     * on the given model, it adds it to the attributes array with
     * a value of null.
     *
     */
    private function getAllAttributes(Model $model): array
    {
        $columns = $model->getFillable();

        $attributes = $model->getAttributes();

        foreach ($columns as $column) {
            if (!array_key_exists($column, $attributes)) {
                $attributes[$column] = null;
            }
        }

        return $attributes;
    }
}