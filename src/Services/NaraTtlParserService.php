<?php

namespace KraenzleRitter\NaraRiskAssessment\Services;

use Illuminate\Support\Facades\Log;
use pietercolpaert\hardf\TriGParser;

/**
 * NARA TTL/RDF Parser Service
 * Parses NARA file formats TTL and extracts PRONOM mappings
 */
class NaraTtlParserService
{
    private array $pronomMappings = [];
    private array $naraFormats = [];

    /**
     * Parse TTL content and extract PRONOM mappings
     */
    public function parseTtl(string $ttlContent): array
    {
        Log::info('Starting NARA TTL parsing with hardf library');

        $this->pronomMappings = [];
        $this->naraFormats = [];

        try {
            // Use hardf library to parse TTL
            $parser = new TriGParser();
            $triples = $parser->parse($ttlContent);

            Log::debug('Hardf parsing result', [
                'triples_count' => count($triples),
                'first_triple_type' => gettype($triples[0] ?? null),
                'first_triple_structure' => is_array($triples[0] ?? null) ? array_keys($triples[0]) : 'not array',
            ]);

            // Group triples by subject
            $resources = [];
            foreach ($triples as $triple) {
                // Handle array format returned by hardf
                if (is_array($triple)) {
                    $subject = $triple['subject'] ?? null;
                    $predicate = $triple['predicate'] ?? null;
                    $object = $triple['object'] ?? null;
                } else {
                    // Handle object format if it exists
                    $subject = method_exists($triple, 'getSubject') ? $triple->getSubject() : null;
                    $predicate = method_exists($triple, 'getPredicate') ? $triple->getPredicate() : null;
                    $object = method_exists($triple, 'getObject') ? $triple->getObject() : null;
                }

                if ($subject && $predicate && $object !== null) {
                    if (! isset($resources[$subject])) {
                        $resources[$subject] = [];
                    }
                    $resources[$subject][] = [
                        'subject' => $subject,
                        'predicate' => $predicate,
                        'object' => $object,
                    ];
                }
            }

            // Process each resource
            foreach ($resources as $subject => $triples) {
                // Only process NARA FileFormat resources
                if (str_contains($subject, 'naraid:NF') || str_contains($subject, 'archives.gov/data/lod/dpframework/id/NF')) {
                    $this->parseResourceFromTriples($subject, $triples);
                }
            }

        } catch (\Exception $e) {
            Log::error('TTL parsing failed', ['error' => $e->getMessage()]);

            return [];
        }

        Log::info('NARA TTL parsing completed', [
            'pronom_mappings' => count($this->pronomMappings),
            'nara_formats' => count($this->naraFormats),
        ]);

        return $this->pronomMappings;
    }

    /**
     * Parse resource from RDF triples using hardf library
     */
    private function parseResourceFromTriples(string $subject, array $triples): void
    {
        $properties = [];

        // Convert triples to properties array
        foreach ($triples as $triple) {
            $predicate = $triple['predicate'];
            $object = $triple['object'];

            // Handle multiple values for same property
            if (isset($properties[$predicate])) {
                if (! is_array($properties[$predicate])) {
                    $properties[$predicate] = [$properties[$predicate]];
                }
                $properties[$predicate][] = $object;
            } else {
                $properties[$predicate] = $object;
            }
        }

        // Look for PRONOM ID via wikidata:p2748
        $pronomId = $this->extractPronomId($properties);

        Log::debug('Parsed resource with hardf', [
            'subject' => $subject,
            'properties_count' => count($properties),
            'has_pronom' => ! empty($pronomId),
            'pronom_id' => $pronomId,
            'has_wikidata_p2748' => isset($properties['http://www.wikidata.org/entity/p2748']),
            'property_keys' => array_slice(array_keys($properties), 0, 10), // First 10 property keys
        ]);

        if ($pronomId) {
            $this->processPronomMapping($subject, $pronomId, $properties);
        }
    }    /**
     * Extract PRONOM ID from wikidata:p2748 property
     */
    private function extractPronomId(array $properties): ?string
    {
        // Look for wikidata:p2748 (full URI format)
        $wikidataP2748 = $properties['http://www.wikidata.org/entity/p2748'] ??
                         $properties['wikidata:p2748'] ?? null;

        if ($wikidataP2748) {
            // Handle both single value and array
            $values = is_array($wikidataP2748) ? $wikidataP2748 : [$wikidataP2748];

            foreach ($values as $val) {
                if (is_string($val) && preg_match('/PRONOM\/(fmt\/\d+|x-fmt\/\d+)/', $val, $matches)) {
                    return $matches[1];
                }
            }
        }

        // Also check for direct pronom references in any property
        foreach ($properties as $key => $value) {
            // Handle array values
            $values = is_array($value) ? $value : [$value];

            foreach ($values as $val) {
                if (is_string($val) && (str_contains($key, 'pronom') || str_contains(strtolower($val), 'fmt/'))) {
                    if (preg_match('/(fmt\/\d+|x-fmt\/\d+)/', $val, $matches)) {
                        return $matches[1];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Process PRONOM mapping and extract NARA properties
     */
    private function processPronomMapping(string $subject, string $pronomId, array $properties): void
    {
        // Extract NARA properties (handle potential arrays)
        $category = $this->extractNaraProperty($properties, 'nara:category');
        $riskLevel = $this->extractNaraProperty($properties, 'nara:riskLevel');
        $preservationAction = $this->extractNaraProperty($properties, 'nara:preservationAction');

        // Get format name and other metadata (check both full URI and prefixed forms)
        $formatName = $properties['http://www.w3.org/2000/01/rdf-schema#label'] ??
                     $properties['rdfs:label'] ??
                     $properties['http://purl.org/dc/terms/title'] ??
                     $properties['dct:title'] ??
                     'Unknown Format';
        $formatName = is_string($formatName) ? trim($formatName, '"') : (string) $formatName;

        // Build mapping entry
        $mapping = [
            'subject' => $subject,
            'pronom_id' => $pronomId,
            'format_name' => $formatName,
            'nara_category' => $category,
            'nara_risk_level' => $riskLevel,
            'nara_preservation_action' => $preservationAction,
            'properties' => $properties,
        ];

        // Store by PRONOM ID
        $this->pronomMappings[$pronomId] = $mapping;
        $this->naraFormats[$subject] = $mapping;

        Log::debug('Processed PRONOM mapping', [
            'pronom_id' => $pronomId,
            'format' => $formatName,
            'category' => $category,
            'risk' => $riskLevel,
            'action' => $preservationAction,
        ]);
    }

    /**
     * Extract NARA property value and resolve naraid reference
     */
    private function extractNaraProperty(array $properties, string $propertyName): ?array
    {
        // Try both full URI and prefixed form
        $fullUri = str_replace('nara:', 'https://www.archives.gov/data/lod/dpframework/def/', $propertyName);
        $value = $properties[$fullUri] ?? $properties[$propertyName] ?? null;

        if (! $value) {
            return null;
        }

        // Handle arrays - take first value for now
        if (is_array($value)) {
            $value = $value[0] ?? null;
            if (! $value) {
                return null;
            }
        }

        // Convert to string if needed
        $value = is_string($value) ? $value : (string) $value;

        // If it's a naraid reference, extract ID and label
        if (str_contains($value, 'naraid:') || str_contains($value, 'archives.gov/data/lod/dpframework/id/')) {
            $naraId = $value;
            $label = $this->resolveNaraIdLabel($naraId);

            return [
                'nara_id' => $naraId,
                'label' => $label,
                'value' => str_replace(['naraid:', 'https://www.archives.gov/data/lod/dpframework/id/'], '', $naraId),
            ];
        }

        // Direct value
        return [
            'nara_id' => null,
            'label' => trim($value, '"'),
            'value' => trim($value, '"'),
        ];
    }

    /**
     * Resolve NARA ID to human-readable label
     */
    private function resolveNaraIdLabel(string $naraId): string
    {
        // Strip URL prefix if present to get just the ID
        $cleanId = str_replace(['https://www.archives.gov/data/lod/dpframework/id/', 'naraid:'], 'naraid:', $naraId);

        // Map common NARA IDs to labels
        $naraLabels = [
            'naraid:Low' => 'Low Risk',
            'naraid:Moderate' => 'Moderate Risk',
            'naraid:High' => 'High Risk',
            'naraid:Retain' => 'Retain',
            'naraid:Assess' => 'Retain for Future Assessment',
            'naraid:Transform' => 'Transform',
            'naraid:Identify' => 'Identify Version',
            'naraid:Audio' => 'Digital Audio',
            'naraid:StillImage' => 'Digital Still Image',
            'naraid:Video' => 'Digital Video',
            'naraid:Textual' => 'Textual and Word Processing',
            'naraid:Presentation' => 'Presentation and Publishing',
            'naraid:Spreadsheets' => 'Spreadsheets',
            'naraid:Databases' => 'Databases',
            'naraid:Email' => 'Email',
            'naraid:Web' => 'Web Records',
            'naraid:StructuredData' => 'Structured Data',
            'naraid:Geospatial' => 'Geospatial',
        ];

        return $naraLabels[$cleanId] ?? str_replace('naraid:', '', $cleanId);
    }

    /**
     * Get all PRONOM mappings
     */
    public function getPronomMappings(): array
    {
        return $this->pronomMappings;
    }

    /**
     * Get specific PRONOM mapping
     */
    public function getPronomMapping(string $pronomId): ?array
    {
        return $this->pronomMappings[$pronomId] ?? null;
    }

    /**
     * Get parsing statistics
     */
    public function getStatistics(): array
    {
        $categoryStats = [];
        $riskStats = [];
        $actionStats = [];

        foreach ($this->pronomMappings as $mapping) {
            if (isset($mapping['nara_category']['value'])) {
                $cat = $mapping['nara_category']['value'];
                $categoryStats[$cat] = ($categoryStats[$cat] ?? 0) + 1;
            }

            if (isset($mapping['nara_risk_level']['value'])) {
                $risk = $mapping['nara_risk_level']['value'];
                $riskStats[$risk] = ($riskStats[$risk] ?? 0) + 1;
            }

            if (isset($mapping['nara_preservation_action']['value'])) {
                $action = $mapping['nara_preservation_action']['value'];
                $actionStats[$action] = ($actionStats[$action] ?? 0) + 1;
            }
        }

        return [
            'total_mappings' => count($this->pronomMappings),
            'categories' => $categoryStats,
            'risk_levels' => $riskStats,
            'actions' => $actionStats,
        ];
    }
}
