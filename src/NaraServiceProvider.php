<?php

namespace KraenzleRitter\NaraRiskAssessment;

use Illuminate\Support\ServiceProvider;
use KraenzleRitter\NaraRiskAssessment\Services\NaraAssessmentService;

class NaraServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge package configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/nara.php',
            'nara'
        );

        // Register singleton service
        $this->app->singleton('nara', function ($app) {
            return $app->make(NaraAssessmentService::class);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/nara.php' => config_path('nara.php'),
            ], 'nara-config');

            // Register commands
            $this->commands([
                \KraenzleRitter\NaraRiskAssessment\Commands\DownloadNaraSchema::class,
            ]);
        }
    }
}
