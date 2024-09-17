<?php

namespace Brainstud\JsonApi\Tests\Unit;

use Brainstud\JsonApi\Tests\Models\Developer;
use Brainstud\JsonApi\Tests\Models\PullRequest;
use Brainstud\JsonApi\Tests\Models\Review;
use Brainstud\JsonApi\Tests\Resources\DeveloperResourceCollection;
use Brainstud\JsonApi\Tests\TestCase;
use Illuminate\Support\Facades\Route;

class JsonApiResourceCollectionTest extends TestCase
{
    public function testBasicResourceCollectionResource()
    {
        $developers = Developer::factory()->count(3)->create();

        Route::get('test-route', fn () => DeveloperResourceCollection::make(Developer::all()));
        $response = $this->getJson('test-route');

        $expcetedData = $developers->map(fn (Developer $developer) => $this->createJsonResource($developer));

        $response->assertExactJson([
            'data' => $expcetedData->toArray(),
        ]);
    }

    public function testCollectionResourceWithRelations()
    {
        $others = Developer::factory(3)->create();
        $developer = Developer::factory()
            ->has(PullRequest::factory(1))
            ->create();

        Route::get('test-route', fn () => DeveloperResourceCollection::make(Developer::with('pullRequests')->get()));
        $response = $this->getJson('test-route?inludes=pullRequests');

        ray(json_decode($response->getContent(), true));

        $response->assertExactJson([
            'data' => [
                ...$this->createJsonResource($others),
                $this->createJsonResource($developer, ['pull_requests' => $developer->pullRequests]),
            ],
            'included' => [
                $this->createJsonResource(
                    $developer->pullRequests->first(),
                    links: ['view' => ['href' => $developer->pullRequests->first()->getShowUrl()]]
                ),
            ],
        ]);
    }

    public function testCollectionResourceEnlargeResourceDepth1()
    {
        $someBloke = Developer::factory()->create();

        $authorClaire = Developer::factory()
            ->has(
                PullRequest::factory()
                    ->has(Review::factory()->for($someBloke, 'reviewer')->count(2))
            )
            ->create();

        $prsClaire = $authorClaire->pullRequests;
        $firstPrClaire = $authorClaire->pullRequests->first();

        Review::factory()
            ->for($authorClaire, 'reviewer')
            ->for($firstPrClaire)
            ->create();

        $authorTom = Developer::factory()
            ->has(
                PullRequest::factory()
                    ->has(Review::factory()->for($authorClaire, 'reviewer')->count(2))
            )
            ->create();

        $prsTom = $authorTom->pullRequests;
        $firstPrTom = $prsTom->first();

        Review::factory()
            ->for($authorTom, 'reviewer')
            ->for($firstPrTom)
            ->create();

        $includes = [
            'pullRequests',
            'pullRequests.reviews',
            'pullRequests.reviews.reviewer',
        ];

        Route::get('test-route', fn () => DeveloperResourceCollection::make([[Developer::with($includes)->find(2), 3]]));
        $response = $this->getJson('test-route?include'.implode(',', $includes));

        $response->assertExactJson([
            'data' => [
                $this->createJsonResource($authorClaire, ['pull_requests' => $prsClaire]),
            ],
            'included' => [
                $this->createJsonResource(
                    $firstPrClaire,
                    ['reviews' => $firstPrClaire->reviews],
                    links: ['view' => ['href' => $firstPrClaire->getShowUrl()]]
                ),
                $this->createJsonResource($firstPrClaire->reviews[0], ['reviewer' => $firstPrClaire->reviews[0]->reviewer]),
                $this->createJsonResource($firstPrClaire->reviews[1], ['reviewer' => $firstPrClaire->reviews[1]->reviewer]),
                $this->createJsonResource($firstPrClaire->reviews[2], ['reviewer' => $firstPrClaire->reviews[2]->reviewer]),
                $this->createJsonResource($firstPrClaire->reviews[0]->reviewer),
                $this->createJsonResource($firstPrClaire->reviews[2]->reviewer),
            ],
        ]);
    }

    public function testAddMetaToResources(): void
    {
        $developers = Developer::factory(3)->create();

        Route::get(
            'test-route',
            fn () => DeveloperResourceCollection::make(Developer::all())
                ->addMetaToResources(
                    fn (Developer $model) => ['hello' => $model->name]
                )
        );

        $response = $this->getJson('test-route');

        $developers->each(fn (Developer $developer) => (
            $response->assertJsonFragment(['meta' => ['hello' => $developer->name]])
        ));
    }
}
