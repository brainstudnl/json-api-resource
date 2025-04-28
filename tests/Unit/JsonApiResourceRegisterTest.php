<?php

namespace Brainstud\JsonApi\Tests\Unit;

use Brainstud\JsonApi\Tests\Models\Account;
use Brainstud\JsonApi\Tests\Models\Comment;
use Brainstud\JsonApi\Tests\Models\Post;
use Brainstud\JsonApi\Tests\Resources\AccountResource;
use Brainstud\JsonApi\Tests\Resources\PostResource;
use Brainstud\JsonApi\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class JsonApiResourceRegisterTest extends TestCase
{
    public function test_basic_resource()
    {
        $account = Account::factory()->create();

        Route::get('test-route', fn () => AccountResource::make($account));
        $response = $this->getJson('test-route');

        $response->assertExactJson([
            'data' => [
                'id' => $account->identifier,
                'type' => 'accounts',
                'attributes' => [
                    'name' => $account->name,
                ],
            ],
        ]);
    }

    public function test_basic_resource_collection()
    {
        $accounts = Account::factory()->count(3)->create();

        Route::get('test-route', fn () => AccountResource::collection($accounts));
        $response = $this->getJson('test-route');

        $response->assertExactJson([
            'data' => $this->createJsonResource($accounts),
        ]);
    }

    public function test_resource_with_empty_relation_loaded()
    {
        $account = Account::factory()
            ->create();

        Route::get('test-route', fn () => (
            AccountResource::make(Account::first()->load('posts'))
        ));
        $response = $this->getJson('test-route');

        $response->assertExactJson([
            'data' => $this->createJsonResource($account),
        ]);
    }

    public function test_related_resource()
    {
        $post = Post::factory()->create();
        $author = $post->author;

        Route::get('test-route', fn (Request $request) => (
            PostResource::make(Post::with($request->query()['includes'])->first())
        ));
        $response = $this->getJson('test-route?includes=author');

        $response->assertExactJson([
            'data' => $this->createJsonResource($post, ['author' => $author]),
            'included' => [$this->createJsonResource($author)],
        ]);
    }

    public function test_related_resources()
    {
        $post = Post::factory()
            ->has(Comment::factory()->count(3))
            ->create();
        $comments = $post->comments;

        Route::get('test-route', fn (Request $request) => (
            PostResource::make(Post::with($request->query()['includes'])->first())
        ));
        $response = $this->getJson('test-route?includes=comments');

        $response->assertExactJson([
            'data' => $this->createJsonResource($post, ['comments' => $comments]),
            'included' => $this->createJsonResource($comments),
        ]);
    }

    public function test_duplicated_related_resources()
    {
        $post = Post::factory()->create();
        $author = $post->author;

        $comment = Comment::factory([
            'account_id' => $author->id,
            'post_id' => $post->id,
        ])->create();

        Route::get('test-route', fn (Request $request) => (
            PostResource::make(Post::with(explode(',', $request->query()['includes']))->first())
        ));
        $response = $this->getJson('test-route?includes=author,comments,comments.commenter');

        $response->assertExactJson([
            'data' => $this->createJsonResource($post, ['author' => $author, 'comments' => [$comment]]),
            'included' => [
                $this->createJsonResource($author),
                $this->createJsonResource($comment, ['commenter' => $author]),
            ],
        ]);
    }

    public function test_deep_related_resource()
    {
        $account = Account::factory()->create();

        $post = Post::factory([
            'author_id' => $account->id,
        ])
            ->has(Comment::factory()->has(Account::factory(), 'commenter'), 'comments')
            ->has(Comment::factory()->for($account, 'commenter'), 'comments')
            ->create();
        $author = $post->author;
        $comments = $post->comments;
        $commenter = $comments->first()->commenter;
        $authorAsCommenter = $comments[1]->commenter;

        Route::get('test-route', fn (Request $request) => (
            AccountResource::make([Account::with(explode(',', $request->query()['includes']))->first(), 3])
        ));
        $response = $this->getJson('test-route?includes=posts,posts.author,posts.comments,posts.comments.commenter');

        $response->assertExactJson([
            'data' => $this->createJsonResource($author, ['posts' => [$post]]),
            'included' => [
                $this->createJsonResource($post, ['author' => $author, 'comments' => $comments]),
                $this->createJsonResource($author),
                $this->createJsonResource($comments[0], ['commenter' => $commenter]),
                $this->createJsonResource($comments[1], ['commenter' => $authorAsCommenter]),
                $this->createJsonResource($commenter),
            ],
        ]);
    }

    public function test_too_deep_related_resource()
    {
        $account = Account::factory()->create();

        $post = Post::factory([
            'author_id' => $account->id,
        ])
            ->has(Comment::factory()->has(Account::factory(), 'commenter'), 'comments')
            ->has(Comment::factory()->for($account, 'commenter'), 'comments')
            ->create();
        $postAuthor = $post->author;
        $comments = $post->comments;
        $commentAuthor = $comments->first()->commenter;

        Route::get('test-route', fn (Request $request) => (
            AccountResource::make([Account::with(explode(',', $request->query()['includes']))->first(), 2])
        ));
        $response = $this->getJson('test-route?includes=posts,posts.author,posts.comments,posts.comments.commenter');

        $response->assertExactJson([
            'data' => $this->createJsonResource($postAuthor, ['posts' => [$post]]),
            'included' => [
                $this->createJsonResource($post, ['author' => $postAuthor, 'comments' => $comments]),
                $this->createJsonResource($postAuthor),
                $this->createJsonResource($comments[0]),
                $this->createJsonResource($comments[1]),
            ],
        ]);
        $response->assertJsonMissing(['id' => $commentAuthor->identifier]);
    }

    public function test_resource_with_meta_data()
    {
        $account = Account::factory()
            ->has(Post::factory()->count(10))
            ->create();
        Route::get('test-route', fn () => (
            AccountResource::make(Account::with('posts')->first())
        ));
        $response = $this->getJson('test-route');

        $response->assertExactJson([
            'data' => $this->createJsonResource(
                modelOrCollection: $account,
                relationships: ['posts' => $account->posts],
                meta: ['experienced_author' => true]
            ),
            'included' => $this->createJsonResource($account->posts),
        ]);
    }

    public function test_resource_with_link_data()
    {
        $link = 'https://some-link-to-blog.com';
        $post = Post::factory([
            'url' => $link,
        ])->create();

        Route::get('test-route', fn () => (
            PostResource::make(Post::first())
        ));
        $response = $this->getJson('test-route');

        $response->assertExactJson([
            'data' => $this->createJsonResource($post, links: ['view' => ['href' => $link]]),
        ]);
    }

    public function test_resource_sparse_fieldset()
    {
        $post = Post::factory()->create();

        Route::get('test-route', fn () => (
            PostResource::make(Post::first())
        ));
        $response = $this->getJson('test-route?fields[posts]=title');

        $response->assertExactJson([
            'data' => $this->createJsonResource($post, onlyAttributes: ['title']),
        ]);
        $response->assertJsonMissing(['content' => $post->content]);
    }

    public function test_resource_included_sparse_fieldset()
    {
        $account = Account::factory(['email' => 'bloke@example.org'])->create();

        $post = Post::factory()
            ->for($account, 'author')
            ->create();

        $author = $post->author;

        Route::get('test-route', fn (Request $request) => (
            PostResource::make(Post::with($request->query()['includes'])->first())
        ));
        $response = $this->getJson('test-route?includes=author&fields[posts]=title&fields[accounts]=email');

        $response->assertExactJson([
            'data' => $this->createJsonResource($post, ['author' => $author], onlyAttributes: ['title']),
            'included' => [
                $this->createJsonResource($author, onlyAttributes: ['email']),
            ],
        ]);
    }

    public function test_resource_double_loaded_does_not_overwrite_but_merge()
    {
        $posterAccount = Account::factory()->create(['email' => 'markie@example.org']);
        $commenterAccount = Account::factory()->create(['email' => 'commenter@example.org']);
        $post = Post::factory()
            ->for($posterAccount, 'author')
            ->create();

        $comment = Comment::factory()->create([
            'post_id' => $post,
            'content' => 'This is the test comment.',
            'account_id' => $commenterAccount,
        ]);

        Route::get('test-route', fn (Request $request) => (
            PostResource::make([Post::with(explode(',', $request->query()['includes']))->first(), 3])
        ));

        $response = $this->getJson('test-route?includes=comments.commenter.comments&meta=merge_data_test');

        $response->assertStatus(200);
        $response->assertExactJson([
            'data' => $this->createJsonResource($post, ['comments' => [$comment]]),
            'included' => [
                $this->createJsonResource($comment, ['commenter' => $commenterAccount], ['firstResourceData' => true, 'secondResourceData' => true]),
                $this->createJsonResource($commenterAccount, ['comments' => [$comment]]),
            ],
        ]);
    }

    public function test_add_metadata_shows_in_repsonse()
    {
        $author = Account::factory()->create();
        $posts = Post::factory(10)->for($author, 'author')->create();

        Route::get('test-route', fn (Request $request) => (
            AccountResource::make([Account::with(explode(',', $request->query()['includes']))->first(), 2])
                ->addMeta(['added_metadata' => true])
        ));

        $response = $this->getJson('test-route?includes=posts');

        $response->assertOk();
        $response->assertJsonFragment(['added_metadata' => true]);

        // The one below comes from the register function (if more than 10 posts)
        $response->assertJsonFragment(['experienced_author' => true]);
    }

    public function test_add_metadata_multiple_times_shows_all()
    {
        Account::factory()->create();

        Route::get('test-route', fn () => (
            AccountResource::make([Account::first(), 2])
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
        $author = Account::factory()->create();
        Post::factory(10)->for($author, 'author')->create();

        Route::get('test-route', fn () => (
            AccountResource::make([Account::first(), 2])
                ->addMeta(['experienced_author' => false])
        ));

        $response = $this->getJson('test-route?includes=posts');

        $response->assertOk();
        $response->assertJsonFragment(['experienced_author' => false]);
        $response->assertJsonMissing(['experienced_author' => true]);
    }
}
