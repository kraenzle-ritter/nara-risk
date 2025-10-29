<?php

namespace KraenzleRitter\NaraRiskAssessment\Tests;

use KraenzleRitter\NaraRiskAssessment\NaraServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            NaraServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Setup the application environment for testing
        $app['config']->set('database.default', 'testing');

        // Disable package discovery in testing to avoid conflicts
        $app['config']->set('app.providers', []);
    }

    protected function defineEnvironment($app)
    {
        // Ensure clean environment for CI
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');

        // Create storage directories if they don't exist
        $storagePath = $app->basePath('storage/app');
        if (! file_exists($storagePath)) {
            mkdir($storagePath, 0o755, true);
        }

        $naraPath = $storagePath . '/nara';
        if (! file_exists($naraPath)) {
            mkdir($naraPath, 0o755, true);
        }
    }
}
