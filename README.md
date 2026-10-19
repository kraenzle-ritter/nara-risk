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
use KraenzleRitter\NaraRisk\Services\NaraAssessmentService;

$service = app(NaraAssessmentService::class);
$result = $service->assessFile('fmt/276'); // PDF 1.7

echo $result['risk_level'];           // "Low"
echo $result['category'];             // "Presentation and Publishing"
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

## Configuration

Publish the configuration with `php artisan vendor:publish --tag=nara-config`, or set the
environment variable. The defaults:

| Key | Default | |
|---|---|---|
| `cache_directory` | `storage/app/nara` | TTL files and the parsed format table |
| `cache_days` | `28` | age after which `fileformats.ttl` is downloaded again |
| `download` | `true` (`NARA_DOWNLOAD`) | `false` on servers without internet access: the file in the cache directory is used however old, and nothing waits for a download |
| `timeout` | `60` | seconds per download |
| `retry_after_minutes` | `360` | a failed download is not retried before; meanwhile the old file is used, or the assessment fails at once if there is none |
| `schema_urls` | archives.gov | `fileformats` is read by the assessment; `nara:download-schema` fetches all |

The parsed table is stored next to the TTL file (`nara_fileformats.ttl.parsed`) and reused
while the file is unchanged, so a queue job assessing a single file does not parse 1 MB of
Turtle each time.

## Testing

Run the test suite:

```bash
composer test
```

Some tests download from archives.gov; `NaraTtlCacheTest` runs without network.

## Requirements

- PHP 8.2+
- Laravel 11.44+ | 12.4+ | 13

## Data Sources

This package uses official data from:

- [NARA Digital Preservation Framework](https://www.archives.gov/preservation/digital-preservation/risk)
- [PRONOM Technical Registry](https://www.nationalarchives.gov.uk/PRONOM/)
- [Archives.gov TTL/RDF files](https://www.archives.gov/preservation/digital-preservation/linked-data)

Data is cached locally and refreshed automatically every 28 days.

## License

This project is licensed under the [MIT License](LICENSE).
