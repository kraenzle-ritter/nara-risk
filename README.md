# NARA Risk Assessment

[![Tests](https://github.com/kraenzle-ritter/nara-risk-assessment/workflows/Tests/badge.svg)](https://github.com/kraenzle-ritter/nara-risk-assessment/actions)
[![Code Style](https://github.com/kraenzle-ritter/nara-risk-assessment/workflows/Code%20Style/badge.svg)](https://github.com/kraenzle-ritter/nara-risk-assessment/actions)
[![Latest Stable Version](https://poser.pugx.org/kraenzle-ritter/nara-risk-assessment/v/stable)](https://packagist.org/packages/kraenzle-ritter/nara-risk-assessment)
[![Total Downloads](https://poser.pugx.org/kraenzle-ritter/nara-risk-assessment/downloads)](https://packagist.org/packages/kraenzle-ritter/nara-risk-assessment)
[![License](https://poser.pugx.org/kraenzle-ritter/nara-risk-assessment/license)](https://packagist.org/packages/kraenzle-ritter/nara-risk-assessment)
[![PHP Version Require](https://poser.pugx.org/kraenzle-ritter/nara-risk-assessment/require/php)](https://packagist.org/packages/kraenzle-ritter/nara-risk-assessment)

A Laravel package for automated digital preservation risk assessment based on the NARA Digital Preservation Framework.

## Features

- Official NARA TTL/RDF data integration
- Automatic caching with 28-day refresh cycle
- Risk assessment for 100+ file formats via PRONOM IDs
- Preservation action recommendations
- Support for 16 NARA format categories
- Tool suggestions for preservation workflows

## Installation

```bash
composer require kraenzle-ritter/nara-risk-assessment
```

The package auto-registers via Laravel's package discovery.

## Usage

```php
use KraenzleRitter\NaraRiskAssessment\Services\NaraAssessmentService;

$service = app(NaraAssessmentService::class);
$result = $service->assessFile('fmt/412'); // PDF 1.7

echo $result['risk_level'];           // "Low"
echo $result['category'];             // "Textual"
echo $result['recommended_action'];   // "Retain"
```

## Response Structure

Each assessment returns an array with these key fields:

- `pronom_id` - PRONOM identifier
- `format_name` - Human-readable format name
- `category` - NARA format category
- `risk_level` - Low, Moderate, or High
- `recommended_action` - Retain, Transform, Assess, or Identify
- `tools` - Array of suggested preservation tools
- `nara_compliant` - Boolean indicating NARA framework coverage
- `assessment_notes` - Detailed recommendations

## Additional Methods

```php
// Get all supported PRONOM IDs
$ids = $service->getSupportedPronomIds();

// Get format statistics
$stats = $service->getFormatStatistics();

// Check cache status
$cacheInfo = $service->getCacheInfo();
```

## Testing

Run the test suite:

```bash
composer test
```

The package includes 44 comprehensive tests covering all functionality.

## Requirements

- PHP 8.2+
- Laravel 11.0+

## Data Sources

This package uses official data from:

- NARA Digital Preservation Framework
- PRONOM Technical Registry
- Archives.gov TTL/RDF files

Data is cached locally and refreshed automatically every 28 days.

## License

MIT License. See LICENSE file for details.
