<?php

namespace KraenzleRitter\NaraRisk\Enums;

enum RiskLevel: string
{
    case LOW = 'Low';
    case MODERATE = 'Moderate';
    case HIGH = 'High';

    public function getIdentifier(): string
    {
        return match($this) {
            self::LOW => 'LR',
            self::MODERATE => 'MR',
            self::HIGH => 'HR',
        };
    }

    public function getLabel(): string
    {
        return match($this) {
            self::LOW => 'Low Risk',
            self::MODERATE => 'Moderate Risk',
            self::HIGH => 'High Risk',
        };
    }

    public function getNaraId(): string
    {
        return match($this) {
            self::LOW => 'naraid:Low',
            self::MODERATE => 'naraid:Moderate',
            self::HIGH => 'naraid:High',
        };
    }
}
