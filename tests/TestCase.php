<?php

namespace MartinLechene\Euria\Tests;

use MartinLechene\Euria\EuriaServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [EuriaServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('euria.api_token', 'test-token-fake');
        $app['config']->set('euria.base_url', 'https://api.infomaniak.com/1/ai');
    }
}
