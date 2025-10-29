<?php

namespace KraenzleRitter\NaraRiskAssessment\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array assessFile(string $pronomId, array $fileData = [])
 * @method static array getSupportedPronomIds()
 * @method static array getFormatStatistics()
 * @method static array getCacheInfo()
 * @method static array getParsingStatistics()
 *
 * @see \KraenzleRitter\NaraRiskAssessment\Services\NaraAssessmentService
 */
class Nara extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'nara';
    }
}
