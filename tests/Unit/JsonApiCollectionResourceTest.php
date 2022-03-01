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

    public function testCollectionResourceEnlargeResourceDepth()
    {
        $base = TestModel::create([
            'identifier' => 'model-1',
            'title' => 'a title',
        ]);
        $relation = TestModel::create([
            'identifier' => 'relation-1',
            'title' => 'a relation',
            'test_model_id' => $base->id,
        ]);
        $subrelation = TestModel::create([
            'identifier' => 'relation-2',
            'title' => 'sub relation',
            'test_model_id' => $relation->id,
        ]);
        $subsubrelation = TestModel::create([
            'identifier' => 'relation-3',
            'title' => 'sub sub relation',
            'test_model_id' => $subrelation->id,
        ]);
        $base->refresh()->load(['relationB', 'relationB.relationB', 'relationB.relationB.relationB']);

        Route::get('test-route', fn() => TestCollectionResource::make([[$base, 3]]));
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
                    'relationships' => [
                        'relation_b' => [
                            'data' => [
                                [
                                    'id' => 'relation-1',
                                    'type' => 'test_resource_with_relations',
                                ]
                            ],
                        ],
                    ],
                ]
            ],
            'included' => [
                [
                    'id' => 'relation-1',
                    'type' => 'test_resource_with_relations',
                    'attributes' => [
                        'title' => 'a relation',
                    ],
                    'relationships' => [
                        'relation_b' => [
                            'data' => [
                                [
                                    'id' => 'relation-2',
                                    'type' => 'test_resource_with_relations',
                                ]
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'relation-2',
                    'type' => 'test_resource_with_relations',
                    'attributes' => [
                        'title' => 'sub relation',
                    ],
                    'relationships' => [
                        'relation_b' => [
                            'data' => [
                                [
                                    'id' => 'relation-3',
                                    'type' => 'test_resource_with_relations',
                                ]
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'relation-3',
                    'type' => 'test_resource_with_relations',
                    'attributes' => [
                        'title' => 'sub sub relation',
                    ],
                ],
            ],
        ]);
    }
}