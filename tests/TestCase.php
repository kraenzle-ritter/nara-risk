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

        // Register services for dependency injection
        $app->bind(
            \KraenzleRitter\NaraRiskAssessment\Services\NaraTtlDownloadService::class,
            fn () => new \KraenzleRitter\NaraRiskAssessment\Services\NaraTtlDownloadService()
        );

        $app->bind(
            \KraenzleRitter\NaraRiskAssessment\Services\NaraTtlParserService::class,
            fn () => new \KraenzleRitter\NaraRiskAssessment\Services\NaraTtlParserService()
        );
    }
}
