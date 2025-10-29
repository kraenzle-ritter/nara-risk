<?php

namespace KraenzleRitter\NaraRiskAssessment\Enums;

enum PreservationAction: string
{
    case RETAIN = 'Retain';
    case ASSESS = 'Assess';
    case TRANSFORM = 'Transform';
    case IDENTIFY = 'Identify';

    public function getLabel(): string
    {
        return match($this) {
            self::RETAIN => 'Retain',
            self::ASSESS => 'Retain for Future Assessment',
            self::TRANSFORM => 'Transform',
            self::IDENTIFY => 'Identify Version',
        };
    }

    public function getNaraId(): string
    {
        return match($this) {
            self::RETAIN => 'naraid:Retain',
            self::ASSESS => 'naraid:Assess',
            self::TRANSFORM => 'naraid:Transform',
            self::IDENTIFY => 'naraid:Identify',
        };
    }

    /**
     * Get recommended tools for transformation
     */
    public function getRecommendedTools(): array
    {
        return match($this) {
            self::TRANSFORM => [
                'format_conversion' => ['pandoc', 'ffmpeg', 'imagemagick', 'libreoffice'],
                'text_extraction' => ['tesseract', 'pdftotext', 'antiword'],
                'validation' => ['jhove', 'veraPDF', 'droid'],
                'migration' => ['fits', 'tika', 'siegfried']
            ],
            self::ASSESS => ['jhove', 'fits', 'droid', 'veraPDF'],
            self::IDENTIFY => ['siegfried', 'file', 'trid', 'droid'],
            self::RETAIN => []
        };
    }
}
