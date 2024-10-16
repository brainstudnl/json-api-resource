<?php

namespace Brainstud\JsonApi\Tests\Models;

class Intern
{
    public int $id;

    public string $name;

    public string $department;

    public function __construct(string $name, string $department)
    {
        $this->id = random_int(0, 100);
        $this->name = $name;
        $this->department = $department;
    }
}
