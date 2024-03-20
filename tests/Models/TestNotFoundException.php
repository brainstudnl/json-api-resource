<?php

namespace Brainstud\JsonApi\Tests\Models;

use Brainstud\JsonApi\Exceptions\NotFoundJsonApiException;

class TestNotFoundException extends NotFoundJsonApiException
{
    protected string $title = "Test Not Found";
}