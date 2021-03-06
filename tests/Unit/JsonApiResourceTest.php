<?php

namespace Brainstud\JsonApi\Tests\Unit;

use Brainstud\JsonApi\Tests\Models\TestModel;
use Brainstud\JsonApi\Tests\Resources\TestResource;
use Brainstud\JsonApi\Tests\Resources\TestResourceWithDescriptionA;
use Brainstud\JsonApi\Tests\Resources\TestResourceWithMetadata;
use Brainstud\JsonApi\Tests\Resources\TestResourceWithRelations;
use Brainstud\JsonApi\Tests\Resources\TestResourceWithResourceRelation;
use Brainstud\JsonApi\Tests\TestCase;
use Illuminate\Support\Facades\Route;

class JsonApiResourceTest extends TestCase
{
    public function testResource()
    {
        $model = (new TestModel([
            'identifier' => 'model-1',
            'title' => 'a title',
        ]));

        Route::get('test-route', fn() => TestResource::make($model));
        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'model-1',
                'type' => 'test_resource',
                'attributes' => [
                    'title' => 'a title',
                ],
            ],
        ]);
    }

    public function testResourceCollection()
    {
        $model1 = (new TestModel([
            'identifier' => 'model-1',
            'title' => 'a title',
        ]));
        $model2 = (new TestModel([
            'identifier' => 'model-2',
            'title' => 'second title',
        ]));

        Route::get('test-route', fn() => TestResource::collection([$model1, $model2]));
        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                [
                    'id' => 'model-1',
                    'type' => 'test_resource',
                    'attributes' => [
                        'title' => 'a title',
                    ],
                ],
                [
                    'id' => 'model-2',
                    'type' => 'test_resource',
                    'attributes' => [
                        'title' => 'second title',
                    ],
                ]
            ],
        ]);
    }

    public function testResourceWithMetadata()
    {
        $model = (new TestModel([
            'identifier' => 'model-1',
            'title' => 'a title',
            'test_count' => 5,
            'edit_link' => 'https://example.com'
        ]));

        Route::get('test-route', fn() => TestResourceWithMetadata::make($model));
        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'model-1',
                'type' => 'test_resource_with_metadata',
                'attributes' => [
                    'title' => 'a title',
                ],
                'meta' => [
                    'test_count' => 5
                ],
                'links' => [
                    'edit' => 'https://example.com'
                ],
            ],
        ]);
    }

    public function testResourceWithSingleRelations()
    {
        $base = (new TestModel([
            'identifier' => 'model-1',
            'title' => 'a title',
        ]));
        $relation = (new TestModel([
            'identifier' => 'relation-1',
            'title' => 'a relation'
        ]));
        $base->relationA()->associate($relation);

        Route::get('test-route', fn() => TestResourceWithRelations::make($base));
        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'model-1',
                'type' => 'test_resource_with_relations',
                'attributes' => [
                    'title' => 'a title',
                ],
                'relationships' => [
                    'relation_a' => [
                        'data' => [
                            'id' => 'relation-1',
                            'type' => 'test_resource',
                        ]
                    ],
                ]
            ],
            'included' => [
                [
                    'id' => 'relation-1',
                    'type' => 'test_resource',
                    'attributes' => [
                        'title' => 'a relation'
                    ],
                ]
            ]
        ]);
    }

    public function testResourceWithMultipleRelations()
    {
        $base = TestModel::create([
            'identifier' => 'model-1',
            'title' => 'a title',
        ]);
        $relation1 = TestModel::create([
            'identifier' => 'relation-1',
            'title' => 'a relation',
            'test_model_id' => $base->id,
        ]);
        $base->relationA()->associate($relation1);
        $base->save();

        TestModel::create([
            'identifier' => 'relation-2',
            'title' => 'relation b',
            'test_model_id' => $base->id,
        ]);
        $base->refresh()->load('relationA', 'relationB');

        Route::get('test-route', fn() => TestResourceWithRelations::make($base)->response()->setStatusCode(200));
        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'model-1',
                'type' => 'test_resource_with_relations',
                'attributes' => [
                    'title' => 'a title',
                ],
                'relationships' => [
                    'relation_a' => [
                        'data' => [
                            'id' => 'relation-1',
                            'type' => 'test_resource',
                        ]
                    ],
                    'relation_b' => [
                        'data' => [
                            [
                                'id' => 'relation-1',
                                'type' => 'test_resource_with_relations',
                            ],
                            [
                                'id' => 'relation-2',
                                'type' => 'test_resource_with_relations',
                            ]
                        ]
                    ],
                ]
            ],
            'included' => [
                [
                    'id' => 'relation-1',
                    'type' => 'test_resource',
                    'attributes' => [
                        'title' => 'a relation'
                    ],
                ],
                [
                    'id' => 'relation-1',
                    'type' => 'test_resource_with_relations',
                    'attributes' => [
                        'title' => 'a relation'
                    ],
                ],
                [
                    'id' => 'relation-2',
                    'type' => 'test_resource_with_relations',
                    'attributes' => [
                        'title' => 'relation b'
                    ],
                ]
            ]
        ]);
    }

    public function testResourceWithResourceAsRelations()
    {
        $base = (new TestModel([
            'identifier' => 'model-1',
            'title' => 'a title',
        ]));
        $relation = (new TestModel([
            'identifier' => 'relation-1',
            'title' => 'a relation'
        ]));
        $base->relationA()->associate($relation);

        Route::get('test-route', fn() => TestResourceWithResourceRelation::make($base)->response()->setStatusCode(200));
        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'model-1',
                'type' => 'test_resource_with_resource_relations',
                'attributes' => [
                    'title' => 'a title',
                ],
                'relationships' => [
                    'relation_a' => [
                        'data' => [
                            'id' => 'relation-1',
                            'type' => 'test_resource',
                        ]
                    ],
                ]
            ],
            'included' => [
                [
                    'attributes' => [
                        'title' => 'a relation'
                    ],
                    'id' => 'relation-1',
                    'type' => 'test_resource'
                ]
            ]
        ]);
    }

    public function testResourceWithEmptyCollectionRelations()
    {
        $base = TestModel::create([
            'identifier' => 'model-1',
            'title' => 'a title',
        ]);
        $base->load('relationB');

        Route::get('test-route', fn() => TestResourceWithRelations::make($base)->response()->setStatusCode(200));
        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'model-1',
                'type' => 'test_resource_with_relations',
                'attributes' => [
                    'title' => 'a title',
                ],
            ],
        ]);
    }

    public function testResourceWithSubRelations()
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
        $base->refresh()->load(['relationB', 'relationB.relationB']);

        Route::get('test-route', fn() => TestResourceWithRelations::make($base)->response()->setStatusCode(200));
        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
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
            ],
            'included' => [
                [
                    'attributes' => [
                        'title' => 'a relation',
                    ],
                    'id' => 'relation-1',
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
                    'type' => 'test_resource_with_relations'
                ],
                [
                    'id' => 'relation-2',
                    'type' => 'test_resource_with_relations',
                    'attributes' => [
                        'title' => 'sub relation',
                    ],
                ]
            ]
        ]);
    }

    public function testResourceExceedResourceDepth()
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

        Route::get('test-route', fn() => TestResourceWithRelations::make($base)->response()->setStatusCode(200));
        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
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
                ]
            ]
        ]);
    }

    public function testResourceEnlargeResourceDepth()
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

        Route::get('test-route', fn() => TestResourceWithRelations::make([$base, 3])->response()->setStatusCode(200));
        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
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
            ]
        ]);
    }

    public function testResourceSparseFieldset()
    {
        $model = (new TestModel([
            'identifier' => 'model-1',
            'title' => 'a title',
            'description' => 'the description',
        ]));

        Route::get('test-route', fn() => TestResourceWithDescriptionA::make($model));
        $response = $this->getJson('test-route?fields[test_resource_with_description_a]=description');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'model-1',
                'type' => 'test_resource_with_description_a',
                'attributes' => [
                    'description' => 'the description',
                ],
            ],
        ]);
    }

    public function testResourceIncludedSparseFieldset()
    {
        $model = (new TestModel([
            'identifier' => 'model-1',
            'title' => 'a title',
            'description' => 'the description',
        ]));
        $relation = (new TestModel([
            'identifier' => 'relation-1',
            'title' => 'a relation',
            'description' => 'related description'
        ]));
        $model->relationA()->associate($relation);

        Route::get('test-route', fn() => TestResourceWithDescriptionA::make($model));
        $response = $this->getJson('test-route?fields[test_resource_with_description_a]=description&fields[test_resource_with_description_b]=title&include=relation_a');

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'id' => 'model-1',
                'type' => 'test_resource_with_description_a',
                'attributes' => [
                    'description' => 'the description',
                ],
                'relationships' => [
                    'relation_a' => [
                        'data' => [
                            'id' => 'relation-1',
                            'type' => 'test_resource_with_description_b',
                        ]
                    ],
                ]
            ],
            'included' => [
                [
                    'id' => 'relation-1',
                    'type' => 'test_resource_with_description_b',
                    'attributes' => [
                        'title' => 'a relation'
                    ],
                ]
            ]
        ]);
    }
}