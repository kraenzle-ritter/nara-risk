<?php

namespace KraenzleRitter\NaraRisk\Tests;

use KraenzleRitter\NaraRisk\Services\NaraTtlDownloadService;
use KraenzleRitter\NaraRisk\Services\NaraTtlParserService;

class NaraTtlParserServiceTest extends TestCase
{
    private NaraTtlParserService $parser;
    private string $sampleTtlContent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new NaraTtlParserService();

        // Get real TTL content for testing
        $downloadService = new NaraTtlDownloadService();
        $this->sampleTtlContent = $downloadService->getTtlContent();
    }

    public function test_parse_ttl_returns_array()
    {
        $result = $this->parser->parseTtl($this->sampleTtlContent);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function test_parse_ttl_extracts_pronom_mappings()
    {
        $mappings = $this->parser->parseTtl($this->sampleTtlContent);

        // Check for known PRONOM IDs
        $this->assertArrayHasKey('fmt/412', $mappings, 'PDF 1.7 should be in mappings');
        $this->assertArrayHasKey('fmt/95', $mappings, 'PDF 1.4 should be in mappings');
    }

    public function test_parsed_mapping_has_required_fields()
    {
        $mappings = $this->parser->parseTtl($this->sampleTtlContent);

        // Get a known format (PDF 1.7)
        $this->assertArrayHasKey('fmt/412', $mappings);
        $pdf17 = $mappings['fmt/412'];

        // Check required fields exist
        $this->assertArrayHasKey('format_name', $pdf17);
        $this->assertArrayHasKey('nara_category', $pdf17);
        $this->assertArrayHasKey('nara_risk_level', $pdf17);
        $this->assertArrayHasKey('nara_preservation_action', $pdf17);
    }

    public function test_category_field_has_value_and_label()
    {
        $mappings = $this->parser->parseTtl($this->sampleTtlContent);
        $pdf17 = $mappings['fmt/412'];

        $this->assertArrayHasKey('value', $pdf17['nara_category']);
        $this->assertArrayHasKey('label', $pdf17['nara_category']);
        $this->assertNotEmpty($pdf17['nara_category']['value']);
        $this->assertNotEmpty($pdf17['nara_category']['label']);
    }

    public function test_risk_level_field_has_value_and_label()
    {
        $mappings = $this->parser->parseTtl($this->sampleTtlContent);
        $pdf17 = $mappings['fmt/412'];

        $this->assertArrayHasKey('value', $pdf17['nara_risk_level']);
        $this->assertArrayHasKey('label', $pdf17['nara_risk_level']);
        $this->assertNotEmpty($pdf17['nara_risk_level']['value']);
    }

    public function test_preservation_action_field_has_value_and_label()
    {
        $mappings = $this->parser->parseTtl($this->sampleTtlContent);
        $pdf17 = $mappings['fmt/412'];

        $this->assertArrayHasKey('value', $pdf17['nara_preservation_action']);
        $this->assertArrayHasKey('label', $pdf17['nara_preservation_action']);
        $this->assertNotEmpty($pdf17['nara_preservation_action']['value']);
    }

    public function test_labels_are_human_readable_not_uris()
    {
        $mappings = $this->parser->parseTtl($this->sampleTtlContent);
        $pdf17 = $mappings['fmt/412'];

        // Labels should NOT contain URI patterns
        $categoryLabel = $pdf17['nara_category']['label'];
        $this->assertStringNotContainsString('http://', $categoryLabel);
        $this->assertStringNotContainsString('naraid:', $categoryLabel);

        // Labels should be actual text
        $this->assertNotEmpty($categoryLabel);
        $this->assertIsString($categoryLabel);
    }

    public function test_multiple_pdf_versions_are_parsed()
    {
        $mappings = $this->parser->parseTtl($this->sampleTtlContent);

        // PDF has many versions in NARA
        $this->assertArrayHasKey('fmt/95', $mappings);  // PDF 1.4
        $this->assertArrayHasKey('fmt/354', $mappings); // PDF 1.6
        $this->assertArrayHasKey('fmt/412', $mappings); // PDF 1.7
    }

    public function test_different_format_categories_are_parsed()
    {
        $mappings = $this->parser->parseTtl($this->sampleTtlContent);

        $categories = [];
        foreach ($mappings as $pronomId => $mapping) {
            $category = $mapping['nara_category']['value'] ?? null;
            if ($category && ! in_array($category, $categories)) {
                $categories[] = $category;
            }
        }

        // Should have multiple categories
        $this->assertGreaterThan(5, count($categories));
        $this->assertContains('Textual', $categories);
    }

    public function test_parser_extracts_nara_ids()
    {
        $mappings = $this->parser->parseTtl($this->sampleTtlContent);
        $pdf17 = $mappings['fmt/412'];

        $this->assertArrayHasKey('nara_id', $pdf17['nara_category']);
        $this->assertArrayHasKey('nara_id', $pdf17['nara_risk_level']);
        $this->assertArrayHasKey('nara_id', $pdf17['nara_preservation_action']);
    }

    public function test_parser_handles_invalid_ttl_gracefully()
    {
        $invalidTtl = "This is not valid TTL content";
        $result = $this->parser->parseTtl($invalidTtl);

        // Should return empty array or handle error gracefully
        $this->assertIsArray($result);
    }

    public function test_parser_extracts_hundreds_of_formats()
    {
        $mappings = $this->parser->parseTtl($this->sampleTtlContent);

        // NARA registry should have many formats
        $this->assertGreaterThan(100, count($mappings), 'NARA should have 100+ formats');
    }

    public function test_parsed_format_name_is_readable()
    {
        $mappings = $this->parser->parseTtl($this->sampleTtlContent);
        $pdf17 = $mappings['fmt/412'];

        $formatName = $pdf17['format_name'];
        $this->assertIsString($formatName);
        $this->assertNotEmpty($formatName);
        // Format name can be NARA ID or human-readable name
        $this->assertTrue(
            str_contains($formatName, 'PDF') || str_contains($formatName, 'NF'),
            "Format name should be readable or contain NARA ID"
        );
    }
}
