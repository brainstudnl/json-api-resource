<?php

namespace Brainstud\JsonApi\Tests;

use Brainstud\JsonApi\Traits\JsonResourceHelper;

class TestCase extends \Orchestra\Testbench\TestCase
{
    use JsonResourceHelper;
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom([
            '--database' => 'testing',
            '--path' => realpath(__DIR__ . '/database/migrations')
        ]);
    }
}