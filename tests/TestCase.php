<?php

namespace KraenzleRitter\NaraRiskAssessment\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use KraenzleRitter\NaraRiskAssessment\NaraServiceProvider;

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
    }
}