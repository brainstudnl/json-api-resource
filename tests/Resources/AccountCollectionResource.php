<?php

namespace Brainstud\JsonApi\Tests\Resources;

use Brainstud\JsonApi\Resources\JsonApiCollectionResource;

class AccountCollectionResource extends JsonApiCollectionResource
{
    public $collects = AccountResource::class;
}