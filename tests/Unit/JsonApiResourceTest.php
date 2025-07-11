<?php

namespace Brainstud\JsonApi\Tests\Unit;

use Brainstud\JsonApi\Tests\Models\Developer;
use Brainstud\JsonApi\Tests\Models\Intern;
use Brainstud\JsonApi\Tests\Models\PullRequest;
use Brainstud\JsonApi\Tests\Models\Review;
use Brainstud\JsonApi\Tests\Resources\DeveloperResource;
use Brainstud\JsonApi\Tests\Resources\InternResource;
use Brainstud\JsonApi\Tests\Resources\PullRequestResource;
use Brainstud\JsonApi\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class JsonApiResourceTest extends TestCase
{
    public function test_basic_resource()
    {
        $developer = Developer::factory()->create();

        Route::get('test-route', fn () => DeveloperResource::make($developer));
        $response = $this->getJson('test-route');

        $response->assertExactJson(['data' => $this->createJsonResource($developer)]);
    }

    public function test_non_eloquent_resource()
    {
        $intern = new Intern('Markie Mark', 'Development');

        Route::get('test-route', fn () => InternResource::make($intern));
        $response = $this->getJson('test-route');

        $response->assertJsonFragment(['id' => $intern->id]);
    }

    public function test_basic_resource_collection()
    {
        $developers = Developer::factory()->count(3)->create();

        Route::get('test-route', fn () => DeveloperResource::collection($developers));
        $response = $this->getJson('test-route');

        $response->assertExactJson([
            'data' => $this->createJsonResource($developers),
        ]);
    }

    public function test_basic_resource_with_optional_field()
    {
        $developer = Developer::factory()->create(['email' => 'markie@brainstud.dev']);

        Route::get('test-route', fn () => DeveloperResource::make($developer));
        $response = $this->getJson('test-route');

        $response->assertExactJson(['data' => $this->createJsonResource($developer)]);
    }

    public function test_resource_with_empty_relation_loaded()
    {
        $account = Developer::factory()
            ->create();

        Route::get('test-route', fn () => (
            DeveloperResource::make(Developer::first()->load('pullRequests'))
        ));
        $response = $this->getJson('test-route');

        $response->assertExactJson([
            'data' => $this->createJsonResource($account),
        ]);
    }

    public function test_single_related_resource()
    {
        $pullRequest = PullRequest::factory()->create();
        $developer = $pullRequest->developer;

        Route::get('test-route', fn (Request $request) => (
            PullRequestResource::make(PullRequest::with($request->query()['includes'])->first())
        ));
        $response = $this->getJson('test-route?includes=developer');

        $response->assertExactJson([
            'data' => $this->createJsonResource(
                $pullRequest,
                ['developer' => $developer],
                links: ['view' => ['href' => $pullRequest->getShowUrl()]]
            ),
            'included' => [$this->createJsonResource($developer)],
        ]);
    }

    public function test_related_resources()
    {
        $pullRequest = PullRequest::factory()
            ->has(Review::factory()->count(3))
            ->create();
        $reviews = $pullRequest->reviews;

        Route::get('test-route', fn (Request $request) => (
            PullRequestResource::make(PullRequest::with($request->query()['includes'])->first())
        ));
        $response = $this->getJson('test-route?includes=reviews');

        $response->assertExactJson([
            'data' => $this->createJsonResource(
                $pullRequest,
                ['reviews' => $reviews],
                links: ['view' => ['href' => $pullRequest->getShowUrl()]]
            ),
            'included' => $this->createJsonResource($reviews),
        ]);
    }

    public function test_duplicated_related_resources()
    {
        $pullRequest = PullRequest::factory()->create();
        $developer = $pullRequest->developer;

        $review = Review::factory()
            ->for($pullRequest)
            ->for($developer, 'reviewer')
            ->create();

        Route::get('test-route', fn (Request $request) => (
            PullRequestResource::make(PullRequest::with(explode(',', $request->query()['includes']))->first())
        ));
        $response = $this->getJson('test-route?includes=developer,reviews,reviews.reviewer');

        $response->assertExactJson([
            'data' => $this->createJsonResource(
                $pullRequest,
                ['developer' => $developer, 'reviews' => [$review]],
                links: ['view' => ['href' => $pullRequest->getShowUrl()]]
            ),
            'included' => [
                $this->createJsonResource($developer),
                $this->createJsonResource($review, ['reviewer' => $developer]),
            ],
        ]);
    }

    public function test_deep_related_resource()
    {
        $developer = Developer::factory()->create();

        $pullRequest = PullRequest::factory()
            ->for($developer)
            ->has(Review::factory()->has(Developer::factory(), 'reviewer'), 'reviews')
            ->has(Review::factory()->for($developer, 'reviewer'), 'reviews')
            ->create();
        $prDeveloper = $pullRequest->developer;
        $reviews = $pullRequest->reviews;
        $reviewer = $reviews->first()->reviewer;
        $authorAsReviewer = $reviews[1]->reviewer;

        Route::get('test-route', fn (Request $request) => (
            DeveloperResource::make([Developer::with(explode(',', $request->query()['includes']))->first(), 3])
        ));
        $response = $this->getJson('test-route?includes=pullRequests,pullRequests.developer,pullRequests.reviews,pullRequests.reviews.reviewer');

        $response->assertExactJson([
            'data' => $this->createJsonResource($prDeveloper, ['pull_requests' => [$pullRequest]]),
            'included' => [
                $this->createJsonResource(
                    $pullRequest,
                    ['developer' => $prDeveloper, 'reviews' => $reviews],
                    links: ['view' => ['href' => $pullRequest->getShowUrl()]]
                ),
                $this->createJsonResource($prDeveloper),
                $this->createJsonResource($reviews[0], ['reviewer' => $reviewer]),
                $this->createJsonResource($reviews[1], ['reviewer' => $authorAsReviewer]),
                $this->createJsonResource($reviewer),
            ],
        ]);
    }

    public function test_too_deep_related_resource()
    {
        $developer = Developer::factory()->create();

        $pullRequest = PullRequest::factory()
            ->for($developer)
            ->has(Review::factory()->has(Developer::factory(), 'reviewer'), 'reviews')
            ->has(Review::factory()->for($developer, 'reviewer'), 'reviews')
            ->create();
        $prDeveloper = $pullRequest->developer;
        $reviews = $pullRequest->reviews;
        $reviewer = $reviews->first()->reviewer;

        Route::get('test-route', fn (Request $request) => (
            DeveloperResource::make([Developer::with(explode(',', $request->query()['includes']))->first(), 2])
        ));
        $response = $this->getJson('test-route?includes=pullRequests,pullRequests.developer,pullRequests.reviews,pullRequests.reviews.reviewer');
        $response->assertExactJson([
            'data' => $this->createJsonResource($prDeveloper, ['pull_requests' => [$pullRequest]]),
            'included' => [
                $this->createJsonResource(
                    $pullRequest,
                    ['developer' => $prDeveloper, 'reviews' => $reviews],
                    links: ['view' => ['href' => $pullRequest->getShowUrl()]]
                ),
                $this->createJsonResource($prDeveloper),
                $this->createJsonResource($reviews[0]),
                $this->createJsonResource($reviews[1]),
            ],
        ]);
        $response->assertJsonMissing(['id' => $reviewer->identifier]);
    }

    public function test_resource_double_loaded_does_not_overwrite_but_merge()
    {
        $prDeveloper = Developer::factory()->create();
        $reviewer = Developer::factory()->create();
        $pullRequest = PullRequest::factory()
            ->for($prDeveloper)
            ->create();

        $review = Review::factory()
            ->for($pullRequest)
            ->for($reviewer, 'reviewer')
            ->create();

        Route::get('test-route', fn (Request $request) => (
            PullRequestResource::make([PullRequest::with(explode(',', $request->query()['includes']))->first(), 3])
        ));

        $response = $this->getJson('test-route?includes=reviews.reviewer.reviews&meta=merge_data_test');

        $response->assertStatus(200);
        $response->assertExactJson([
            'data' => $this->createJsonResource(
                $pullRequest,
                ['reviews' => [$review]],
                links: ['view' => ['href' => $pullRequest->getShowUrl()]]
            ),
            'included' => [
                $this->createJsonResource($review, ['reviewer' => $reviewer], ['firstResourceData' => true, 'secondResourceData' => true]),
                $this->createJsonResource($reviewer, ['reviews' => [$review]]),
            ],
        ]);
    }

    public function test_add_metadata_shows_in_repsonse()
    {
        $developer = Developer::factory()
            ->has(PullRequest::factory(10))
            ->create();

        Route::get('test-route', fn (Request $request) => (
            DeveloperResource::make([Developer::with(explode(',', $request->query()['includes']))->first(), 2])
                ->addMeta(['added_metadata' => true])
        ));

        $response = $this->getJson('test-route?includes=pullRequests');

        $response->assertOk();
        $response->assertJsonFragment(['added_metadata' => true]);

        // The one below comes from the register function (if more than 10 posts)
        $response->assertJsonFragment(['experienced_developer' => true]);
    }

    public function test_add_metadata_multiple_times_shows_all()
    {
        Developer::factory()->create();

        Route::get('test-route', fn () => (
            DeveloperResource::make([Developer::first(), 2])
                ->addMeta(['added_metadata' => true])
                ->addMeta(['extra_metadata' => true])
        ));

        $response = $this->getJson('test-route');

        $response->assertOk();
        $response->assertJsonFragment(['added_metadata' => true]);
        $response->assertJsonFragment(['extra_metadata' => true]);
    }

    public function test_add_metadata_overwrites_existing_keys()
    {
        Developer::factory()
            ->has(PullRequest::factory(10))
            ->create();

        Route::get('test-route', fn () => (
            DeveloperResource::make([Developer::first(), 2])
                ->addMeta(['experienced_author' => false])
        ));

        $response = $this->getJson('test-route?includes=posts');

        $response->assertOk();
        $response->assertJsonFragment(['experienced_author' => false]);
        $response->assertJsonMissing(['experienced_author' => true]);
    }

    public function test_resource_sparse_fieldset()
    {
        $pullRequest = PullRequest::factory()->create();

        Route::get('test-route', fn () => (
            PullRequestResource::make(PullRequest::first())
        ));
        $response = $this->getJson('test-route?fields[pull_requests]=title');

        $response->assertExactJson([
            'data' => $this->createJsonResource(
                $pullRequest,
                onlyAttributes: ['title'],
                links: ['view' => ['href' => $pullRequest->getShowUrl()]],
            ),
        ]);
        $response->assertJsonMissing(['description' => $pullRequest->description]);
    }

    public function test_resource_included_sparse_fieldset()
    {
        $developer = Developer::factory(['email' => 'bloke@example.org'])->create();

        $pullRequest = PullRequest::factory()
            ->for($developer)
            ->create();

        $author = $pullRequest->developer;

        Route::get('test-route', fn (Request $request) => (
            PullRequestResource::make(PullRequest::with($request->query()['includes'])->first())
        ));
        $response = $this->getJson('test-route?includes=developer&fields[pull_requests]=title&fields[developers]=email');

        $response->assertExactJson([
            'data' => $this->createJsonResource(
                $pullRequest,
                ['developer' => $developer], onlyAttributes: ['title'],
                links: ['view' => ['href' => $pullRequest->getShowUrl()]],
            ),
            'included' => [
                $this->createJsonResource($developer, onlyAttributes: ['email']),
            ],
        ]);
    }
}
