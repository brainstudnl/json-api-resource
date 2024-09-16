<?php

namespace Brainstud\JsonApi\Tests\Resources;

use Brainstud\JsonApi\Resources\JsonApiResourceCollection;

class AccountResourceCollection extends JsonApiResourceCollection
{
    public $collects = AccountResource::class;
}
