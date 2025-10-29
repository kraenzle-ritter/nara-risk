# NARA Risk Assessment

[![Tests](https://github.com/kraenzle-ritter/nara-risk/workflows/Tests/badge.svg)](https://github.com/kraenzle-ritter/nara-risk/actions)
[![Code Style](https://github.com/kraenzle-ritter/nara-risk/workflows/Code%20Style/badge.svg)](https://github.com/kraenzle-ritter/nara-risk/actions)
[![codecov](https://codecov.io/gh/kraenzle-ritter/nara-risk/branch/main/graph/badge.svg)](https://codecov.io/gh/kraenzle-ritter/nara-risk)
[![PHPStan Level 5](https://img.shields.io/badge/PHPStan-Level%205-brightgreen.svg)](https://phpstan.org/)
[![Latest Stable Version](https://poser.pugx.org/kraenzle-ritter/nara-risk/v/stable)](https://packagist.org/packages/kraenzle-ritter/nara-risk)
[![Total Downloads](https://poser.pugx.org/kraenzle-ritter/nara-risk/downloads)](https://packagist.org/packages/kraenzle-ritter/nara-risk)
[![License](https://poser.pugx.org/kraenzle-ritter/nara-risk/license)](https://packagist.org/packages/kraenzle-ritter/nara-risk)
[![PHP Version Require](https://poser.pugx.org/kraenzle-ritter/nara-risk/require/php)](https://packagist.org/packages/kraenzle-ritter/nara-risk)

A Laravel package for automated digital preservation risk assessment based on the NARA Digital Preservation Framework.

## Features

- Official NARA TTL/RDF data integration
- Automatic caching with 28-day refresh cycle
- Nara Risk assessment for 100+ file formats via PRONOM IDs
- Nara Preservation action recommendations
- Support for 16 NARA format categories
- Nara Tool suggestions for preservation workflows

## Installation

```bash
composer require kraenzle-ritter/nara-risk
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
- Laravel 11.44+ | 12.4+

## Data Sources

This package uses official data from:

- NARA Digital Preservation Framework
- PRONOM Technical Registry
- Archives.gov TTL/RDF files

Data is cached locally and refreshed automatically every 28 days.

## License

MIT License. See LICENSE file for details.
