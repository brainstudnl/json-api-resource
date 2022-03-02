# JSON:API Resource for Laravel
Make your Laravel API [JSON:API](https://jsonapi.org/) compliant with the `Brainstud\JsonApi` package.

## Example usage
```php
// Course.php

/**
 * @property int $id
 * @property string $title
 * @property string $description
 * @property Carbon $created_at
 * @property Collection $enrollments
 */
class Course extends Model 
{
    protected $fillable = [
        'title',
        'description',
    ];
    
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
}

// CourseResource.php

/**
 * @property Course $resourceObject 
 */
class CourseResource extends JsonApiResource
{
    protected function register(): array
    {
        return [
            'id' => $this->resourceObject->id,
            'type' => 'courses',
            'attributes' => [
                'title' => $this->resourceObject->title,
                'description' => $this->resourceObject->description,
                'created_at' => $this->resourceObject->created_at->format('c'),
            ],
            'relationships' => [
                'enrollments' => ['enrollments', EnrollmentResourceCollection::class],
            ],
        ];
    }
}

// CoursesController.php

class CoursesController
{
    public function index(IndexCoursesRequest $request)
    {
        $query = (new CoursesQueryBuilder)->jsonPaginate();
        return new CourseResourceCollection($query);
    }
    
    public function show(ShowCourseRequest $request, Course $course)
    {
        $query = (new CoursesQueryBuilder)->find($course->id);
        return new CourseResource($query);
    }
}
```

## Installation
1. Add the brainstud group registry to `composer.json`

```
"repositories": {
    "3254464": {
        "type": "composer",
        "url": "https://gitlab.com/api/v4/group/3254464/-/packages/composer/"
    }
},
```

2. Add your gitlab token to the composer config
`composer config --global --auth gitlab-token.gitlab.com YOUR_TOKEN`

3. Require the package
`composer require brainstud/json-api`

## Usage
- Let your resource object extend from `JsonApiResource` instead of `JsonResource`.
- Implement a `register` method that returns the following array. The register has access to `$this->resourceObject` which contains the current model

```php
protected function register(): array
{
    return [
        'id' => $this->resourceObject->identifier,
        'type' => 'object_type',
        'attributes' => [
            'field' => $this->resourceObject->field,
            'other_field' => $this->resourceObject->other_field,
        ],
        'relationships' => [
            'items' => ['items', ItemsResourceCollection::class],
            'item' => ['item', ItemResource::class],
        ],
        'meta' => [
            'some_data' => 'some value',         
        ],
    ];
}
```

## Relationships
[JSON:API: Includes](https://jsonapi.org/format/#fetching-includes)
For the relationships to be included they need to be loaded. This can be done by implementing a `?include` parameter or using [spatie/laravel-query-builder](https://spatie.be/docs/laravel-query-builder/v3/introduction).

## Tweak response
The `register` method doesn't have access to `$request` like `toArray` of `JsonResource` has.
If you want to manipulate the response based on the request this can be done by overriding the `addToResponse` method.

```php
protected function addToResponse($request, $response): array
{
    if ($this->requestWantsMeta($request, 'data')
        && ($data = $this->getData())
    ) {
        $response['meta']['data'] = $data;
    }

    return $response;
}
````

## Resource depth
The resource depth has a default of 2. This can be changed by passing an array to the resource where the second item is the required resource depth.
In the following example we use a depth of 3:

```php
public function show(ShowCourseRequest $request, Course $course)
{
    $query = (new CoursesQueryBuilder)->find($course->id);
    return new CourseResource([$query, 3]);
}
```

Which allows us to ask for an include nested 3 levels deep: `/courses/{identifier}?include=content,content.answers,content.answers.feedback`

## Exception handler
This package contains an exception handler to render exceptions as JSON:API error messages.
Either use this handler directly by editing your `app.php` and registering this singleton

```php
// app.php
$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    \Brainstud\JsonApi\Handlers\JsonApiExceptionHandler::class
);
```

Or register your own exception handler and delegate the render to the `JsonApiExceptionHandler::render` method.

```php
// app.php
$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

// handler.php
public function render($request, Throwable $exception)
{
    if ($request->wantsJson()) {
        return (new JsonApiExceptionHandler($this->container))->render($request, $exception);
    }

    return parent::render($request, $exception);
}
```

### Return error response
There are multiple ways to return an error page

```php
// Throw an exception that will be handled by the JsonApiExceptionHandler
throw new UnprocessableEntityHttpException();

// Return a defined error response
return (new UnprocessableEntityError)->response();

// Return a custom error response
return ErrorResponse::make(new DefaultError(
    'PROCESSING_ERROR',
    'Could not save item',
    'An error occurred during saving of the item'
), Response::HTTP_INTERNAL_SERVER_ERROR);
```

## License
JsonApi is open-sourced software licensed under the [MIT Licence](LICENSE)