# NARA Risk Assessment for Laravel

[![Tests](https://img.shields.io/badge/tests-passing-brightgreen)](https://github.com/kraenzle-ritter/nara-risk-assessment)
[![PHP Version](https://img.shields.io/badge/php-%5E8.2-blue)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/laravel-%5E11.0-red)](https://laravel.com/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

A Laravel package for automated digital preservation risk assessment based on the official **NARA (National Archives and Records Administration) Digital Preservation Framework**.

This package downloads and parses NARA's TTL/RDF ontology data to assess preservation risks for file formats identified by PRONOM IDs, providing actionable preservation recommendations.

## Features

- 🎯 **Official NARA Data**: Uses authentic TTL/RDF data from archives.gov
- 🔄 **Automatic Caching**: 28-day cache with automatic refresh
- 📊 **Comprehensive Assessment**: Risk levels, categories, and preservation actions
- 🛠️ **Tool Recommendations**: Suggests appropriate tools for each action
- 🏷️ **16 Format Categories**: From Audio to Web Records
- ⚡ **100+ Format Mappings**: Extensive PRONOM coverage
- 🧪 **69 Tests**: Fully tested with PHPUnit

## Installation

```bash
composer require kraenzle-ritter/nara-risk-assessment
```

The package will auto-register via Laravel's package discovery.

## Quick Start

```php
use KraenzleRitter\NaraRiskAssessment\Services\NaraAssessmentService;

$service = new NaraAssessmentService();
$assessment = $service->assessFile('fmt/412'); // PDF 1.7

// Access assessment data
echo $assessment['risk_level'];           // "Low"
echo $assessment['category'];             // "Textual and Word Processing"
echo $assessment['recommended_action'];   // "Retain"
```

## Response Fields

The `assessFile()` method returns an array with **17 fields**:

| Field | Type | Description |
|-------|------|-------------|
| `pronom_id` | string | PRONOM identifier (e.g., "fmt/412") |
| `format_name` | string | Human-readable format name |
| `category` | string\|null | NARA category label |
| `category_label` | string | Full category description |
| `nara_category_id` | string\|null | NARA category URI |
| `nara_format_id` | string\|null | NARA format ID (e.g., "NF00311") |
| `risk_level` | string | "Low", "Moderate", or "High" |
| `risk_label` | string | Full risk description |
| `nara_risk_id` | string\|null | NARA risk level URI |
| `recommended_action` | string | "Retain", "Transform", "Identify", or "Assess" |
| `action_label` | string | Full action description |
| `nara_action_id` | string\|null | NARA action URI |
| `tools` | array | Recommended preservation tools |
| `nara_compliant` | bool | Format is in NARA framework |
| `ttl_source` | bool | Data sourced from TTL files |
| `assessment_notes` | string | Detailed assessment notes |
| `nara_subject` | string\|null | Original RDF subject URI |

<details>
<summary><strong>Full Response Example</strong></summary>

```php
[
    'pronom_id' => 'fmt/412',
    'format_name' => 'Portable Document Format 1.7',
    'category' => 'Textual and Word Processing',
    'category_label' => 'Textual and Word Processing',
    'nara_category_id' => 'naraid:Textual',
    'nara_format_id' => 'NF00311',
    'risk_level' => 'Low',
    'risk_label' => 'Low Risk',
    'nara_risk_id' => 'naraid:Low',
    'recommended_action' => 'Retain',
    'action_label' => 'Retain',
    'nara_action_id' => 'naraid:Retain',
    'tools' => [],
    'nara_compliant' => true,
    'ttl_source' => true,
    'assessment_notes' => 'Format assessed using official NARA/DPLA ontology data...',
    'nara_subject' => 'https://www.archives.gov/data/lod/dpframework/id/NF00311'
]
```
</details>

## NARA Categories

The package recognizes all **16 NARA format categories**:

- 📄 **Textual** - Word Processing
- 🖼️ **StillImage** - Digital Images
- 🎬 **Video** - Digital Video
- 🎵 **Audio** - Digital Audio
- 📊 **Spreadsheets**
- 📽️ **Presentation** - Publishing
- 🗄️ **Databases**
- 📧 **Email**
- 🌐 **Web** - Web Records
- 📐 **DesignVector** - Vector Graphics
- 📋 **StructuredData**
- 🗺️ **Geospatial**
- 🎞️ **Cinema** - Digital Cinema
- 🧭 **NavCharts** - Navigational Charts
- 💻 **Code** - Software
- 📅 **Calendars**

## Additional Methods

### Get Supported Formats

```php
$pronomIds = $service->getSupportedPronomIds();
// ['fmt/412', 'fmt/95', 'fmt/353', ...]
```

### Get Format Statistics

```php
$stats = $service->getFormatStatistics();
/*
[
    'total_formats' => 150,
    'by_category' => ['Textual' => 45, 'StillImage' => 38, ...],
    'by_risk' => ['Low' => 67, 'Moderate' => 58, 'High' => 25],
    'by_action' => ['Retain' => 67, 'Transform' => 45, ...]
]
*/
```

### Cache Management

```php
use KraenzleRitter\NaraRiskAssessment\Services\NaraTtlDownloadService;

$downloadService = new NaraTtlDownloadService();

// Get cache information
$info = $downloadService->getCacheInfo();

// Force cache refresh
$downloadService->refreshCache();
```

## Testing

The package includes comprehensive tests:

```bash
composer test
```

**Test Coverage:**
- ✅ 13 Enum Tests
- ✅ 11 Download Service Tests
- ✅ 13 Parser Service Tests
- ✅ 18 Assessment Service Tests
- ✅ 14 Integration Tests

**Total: 69 tests with 222 assertions**

## Data Source

This package uses official NARA TTL/RDF data from:
- **NARA Digital Preservation Framework**: https://www.archives.gov/preservation/electronic-records/digital-preservation-framework
- **TTL Data**: https://www.archives.gov/files/lod/dpframework/fileformats.ttl
- **PRONOM Registry**: https://www.nationalarchives.gov.uk/PRONOM/

Data is automatically cached for 28 days and refreshed as needed.

## Requirements

- PHP 8.2 or higher
- Laravel 11.0 or higher
- pietercolpaert/hardf ^0.5 (TTL/RDF parser)

## License

MIT License. See [LICENSE](LICENSE) file for details.

## Credits

- **NARA** - National Archives and Records Administration
- **DPLA** - Digital Public Library of America
- **PRONOM** - The National Archives (UK)

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Support

For issues, questions, or suggestions, please open an issue on GitHub.
