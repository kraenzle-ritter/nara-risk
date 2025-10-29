<?php

namespace KraenzleRitter\NaraRiskAssessment\Enums;

enum Category: string
{
    case AUDIO = 'Audio';
    case CALENDARS = 'Calendars';
    case CINEMA = 'Cinema';
    case CODE = 'Code';
    case DATABASES = 'Databases';
    case DESIGN_VECTOR = 'DesignVector';
    case EMAIL = 'Email';
    case GEOSPATIAL = 'Geospatial';
    case NAV_CHARTS = 'NavCharts';
    case PRESENTATION = 'Presentation';
    case SPREADSHEETS = 'Spreadsheets';
    case STILL_IMAGE = 'StillImage';
    case STRUCTURED_DATA = 'StructuredData';
    case TEXTUAL = 'Textual';
    case VIDEO = 'Video';
    case WEB = 'Web';

    public function getLabel(): string
    {
        return match($this) {
            self::AUDIO => 'Digital Audio',
            self::CALENDARS => 'Calendars',
            self::CINEMA => 'Digital Cinema',
            self::CODE => 'Software and Code',
            self::DATABASES => 'Databases',
            self::DESIGN_VECTOR => 'Digital Design and Vector Graphics',
            self::EMAIL => 'Email',
            self::GEOSPATIAL => 'Geospatial',
            self::NAV_CHARTS => 'Navigational Charts',
            self::PRESENTATION => 'Presentation and Publishing',
            self::SPREADSHEETS => 'Spreadsheets',
            self::STILL_IMAGE => 'Digital Still Image',
            self::STRUCTURED_DATA => 'Structured Data',
            self::TEXTUAL => 'Textual and Word Processing',
            self::VIDEO => 'Digital Video',
            self::WEB => 'Web Records',
        };
    }

    public function getNaraId(): string
    {
        return 'naraid:' . $this->value;
    }

    /**
     * Get PRONOM format patterns that belong to this category
     */
    public function getPronomPatterns(): array
    {
        return match($this) {
            self::AUDIO => ['fmt/1', 'fmt/2', 'fmt/3', 'fmt/132', 'fmt/141', 'fmt/142', 'fmt/527'],
            self::STILL_IMAGE => ['fmt/11', 'fmt/12', 'fmt/13', 'fmt/41', 'fmt/42', 'fmt/43', 'fmt/353'],
            self::VIDEO => ['fmt/5', 'fmt/6', 'fmt/199', 'fmt/279', 'fmt/355', 'fmt/573'],
            self::TEXTUAL => ['fmt/40', 'fmt/412', 'fmt/609', 'fmt/95', 'fmt/96', 'fmt/97', 'fmt/276'],
            self::PRESENTATION => ['fmt/126', 'fmt/215', 'fmt/388', 'fmt/758'],
            self::SPREADSHEETS => ['fmt/55', 'fmt/56', 'fmt/57', 'fmt/61', 'fmt/214'],
            self::DATABASES => ['fmt/111', 'fmt/293', 'fmt/337', 'fmt/440'],
            self::EMAIL => ['fmt/278', 'fmt/950'],
            self::WEB => ['fmt/96', 'fmt/471', 'fmt/102'],
            self::CODE => ['fmt/609', 'fmt/597', 'x-fmt/265'],
            self::STRUCTURED_DATA => ['fmt/101', 'fmt/817', 'fmt/863'],
            self::GEOSPATIAL => ['fmt/235', 'fmt/236'],
            default => []
        };
    }
}
