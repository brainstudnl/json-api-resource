<?php

namespace Brainstud\JsonApi;

use Illuminate\Support\ServiceProvider as SupportServiceProvider;

class ServiceProvider extends SupportServiceProvider
{
    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        $this->loadJsonTranslationsFrom(__DIR__.'/../lang', 'brainstud/json-api-resource');
    }
}
