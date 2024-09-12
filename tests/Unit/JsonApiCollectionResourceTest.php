<?php

namespace Brainstud\JsonApi\Tests\Unit;

use Brainstud\JsonApi\Tests\Models\Account;
use Brainstud\JsonApi\Tests\Models\Comment;
use Brainstud\JsonApi\Tests\Models\Post;
use Brainstud\JsonApi\Tests\Resources\AccountResourceCollection;
use Brainstud\JsonApi\Tests\Resources\MethodBasedResourceCollection;
use Brainstud\JsonApi\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class JsonApiCollectionResourceTest extends TestCase
{
    public function testBasicResourceCollectionResource()
    {
        $accounts = Account::factory()->count(3)->create();

        Route::get('test-route', fn () => AccountResourceCollection::make(Account::all()));
        $response = $this->getJson('test-route');

        $response->assertExactJson([
            'data' => [
                $this->createJsonResource($accounts[0]),
                $this->createJsonResource($accounts[1]),
                $this->createJsonResource($accounts[2]),
            ],
        ]);
    }

    public function testCollectionResourceWithRelations()
    {

        $others = Account::factory()->count(3)->create();
        $author = Account::factory()->has(Post::factory())->create();

        Route::get('test-route', fn () => AccountResourceCollection::make(Account::with('posts')->get()));
        $response = $this->getJson('test-route?include=posts');

        $response->assertExactJson([
            'data' => [
                ...$this->createJsonResource($others),
                $this->createJsonResource($author, ['posts' => $author->posts]),
            ],
            'included' => [
                $this->createJsonResource($author->posts->first()),
            ],
        ]);
    }

    public function testCollectionResourceEnlargeResourceDepth1()
    {
        $someBloke = Account::factory()->create();

        $authorClaire = Account::factory()
            ->has(
                Post::factory()
                    ->has(Comment::factory()->for($someBloke, 'commenter')->count(2))
            )
            ->create();
        $postsClaire = $authorClaire->posts;
        $postClaire = $authorClaire->posts->first();
        Comment::factory([
            'account_id' => $authorClaire->id,
            'post_id' => $postClaire->id,
        ])->create();

        $authorTom = Account::factory()
            ->has(
                Post::factory()
                    ->has(Comment::factory()->for($authorClaire, 'commenter')->count(2))
            )
            ->create();
        $postsTom = $authorTom->posts;
        $postTom = $postsTom->first();
        Comment::factory([
            'account_id' => $authorTom->id,
            'post_id' => $postsTom->first()->id,
        ])->create();

        $includes = [
            'posts',
            'posts.comments',
            'posts.comments.commenter',
        ];

        Route::get('test-route', fn () => AccountResourceCollection::make([[Account::with($includes)->find(2), 3]]));
        $response = $this->getJson('test-route?include'.implode(',', $includes));

        $response->assertExactJson([
            'data' => [
                $this->createJsonResource($authorClaire, ['posts' => $postsClaire]),
            ],
            'included' => [
                $this->createJsonResource($postClaire, ['comments' => $postClaire->comments]),
                $this->createJsonResource($postClaire->comments[0], ['commenter' => $postClaire->comments[0]->commenter]),
                $this->createJsonResource($postClaire->comments[1], ['commenter' => $postClaire->comments[1]->commenter]),
                $this->createJsonResource($postClaire->comments[2], ['commenter' => $postClaire->comments[2]->commenter]),
                $this->createJsonResource($postClaire->comments[0]->commenter),
                $this->createJsonResource($postClaire->comments[2]->commenter),
            ],
        ]);
    }

    public function testAddMetaToResources(): void
    {
        $accounts = Account::factory()->count(3)->create();

        Route::get(
            'test-route',
            fn () => AccountResourceCollection::make(Account::all())
                ->addMetaToResources(
                    fn (Account $model) => ['hello' => $model->name]
                )
        );

        $response = $this->getJson('test-route');

        $accounts->each(function (Account $account) use ($response) {
            $response->assertJsonFragment(['meta' => ['hello' => $account->name]]);
        });
    }

    public function testFromMethodsCreation(): void
    {
        $account = Account::factory()->create();
        $comments = Comment::factory(5)->for($account, 'commenter')->create();

        Route::get('test-route', fn (Request $request) => (
            MethodBasedResourceCollection::make(Comment::with($request->query()['include'])->get())
        ));

        $response = $this->getJson('test-route?include=commenter');

        $response->assertOk();

        // all comments included in response
        foreach ($comments as $comment) {
            $response->assertJsonFragment($this->createJsonResource($comment));
        }

        $response->assertJsonFragment(['included' => [$this->createJsonResource($account)]]);
    }
}
