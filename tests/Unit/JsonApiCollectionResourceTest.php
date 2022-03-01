<?php

namespace Brainstud\JsonApi\Tests\Unit;

use Brainstud\JsonApi\Tests\Models\TestModel;
use Brainstud\JsonApi\Tests\Resources\TestCollectionResource;
use Brainstud\JsonApi\Tests\TestCase;
use Illuminate\Support\Facades\Route;

class JsonApiCollectionResourceTest extends TestCase
{
    public function testCollectionResource()
    {
        $model1 = (new TestModel([
            'identifier' => 'model-1',
            'title' => 'a title',
        ]));
        $model2 = (new TestModel([
            'identifier' => 'model-2',
            'title' => 'second title',
        ]));

        Route::get('test-route', fn() => TestCollectionResource::make([$model1, $model2]));
        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                [
                    'id' => 'model-1',
                    'type' => 'test_resource_with_relations',
                    'attributes' => [
                        'title' => 'a title',
                    ],
                ],
                [
                    'id' => 'model-2',
                    'type' => 'test_resource_with_relations',
                    'attributes' => [
                        'title' => 'second title',
                    ],
                ]
            ],
        ]);
    }

    public function testCollectionResourceWithRelations()
    {
        $model1 = TestModel::create([
            'identifier' => 'model-1',
            'title' => 'a title',
        ]);
        $model2 = TestModel::create([
            'identifier' => 'model-2',
            'title' => 'second title',
        ]);
        $model3 = TestModel::create([
            'identifier' => 'model-3',
            'title' => 'third title',
            'test_model_id' => $model2->id
        ]);
        $model2->load('relationB');

        Route::get('test-route', fn() => TestCollectionResource::make([$model1, $model2]));
        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                [
                    'id' => 'model-1',
                    'type' => 'test_resource_with_relations',
                    'attributes' => [
                        'title' => 'a title',
                    ],
                ],
                [
                    'id' => 'model-2',
                    'type' => 'test_resource_with_relations',
                    'attributes' => [
                        'title' => 'second title',
                    ],
                    'relationships' => [
                        'relation_b' => [
                            'data' => [[
                                'id' => 'model-3',
                                'type' => 'test_resource_with_relations',
                            ]]
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'id' => 'model-3',
                    'type' => 'test_resource_with_relations',
                    'attributes' => [
                        'title' => 'third title',
                    ],
                ],
            ],
        ]);
    }
}