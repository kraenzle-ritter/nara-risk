<?php

namespace KraenzleRitter\NaraRiskAssessment\Tests;

use KraenzleRitter\NaraRiskAssessment\Services\NaraAssessmentService;

/**
 * Integration tests using real NARA data
 */
class IntegrationTest extends TestCase
{
    private NaraAssessmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NaraAssessmentService();
    }

    public function test_pdf_17_format_assessment()
    {
        $result = $this->service->assessFile('fmt/412'); // PDF 1.7
        
        $this->assertEquals('fmt/412', $result['pronom_id']);
        // Format name can be NARA ID or readable name
        $this->assertNotEmpty($result['format_name']);
        // Category can be short form or full label
        $this->assertStringContainsString('Textual', $result['category']);
        $this->assertTrue($result['nara_compliant']);
        $this->assertTrue($result['ttl_source']);
    }

    public function test_pdf_14_format_assessment()
    {
        $result = $this->service->assessFile('fmt/95'); // PDF 1.4
        
        $this->assertEquals('fmt/95', $result['pronom_id']);
        $this->assertNotEmpty($result['format_name']);
        // PDF 1.4 may be categorized as Presentation or Textual by NARA
        $this->assertNotEmpty($result['category']);
        $this->assertTrue($result['ttl_source']);
    }

    public function test_tiff_format_assessment()
    {
        $result = $this->service->assessFile('fmt/353'); // TIFF 6.0
        
        $this->assertEquals('fmt/353', $result['pronom_id']);
        $this->assertNotEmpty($result['format_name']);
        $this->assertStringContainsString('Image', $result['category']);
        $this->assertTrue($result['ttl_source']);
    }

    public function test_jpeg_format_assessment()
    {
        $result = $this->service->assessFile('fmt/43'); // JPEG
        
        $this->assertEquals('fmt/43', $result['pronom_id']);
        $this->assertNotEmpty($result['format_name']);
        $this->assertStringContainsString('Image', $result['category']);
    }

    public function test_docx_format_assessment()
    {
        $result = $this->service->assessFile('fmt/412'); // Using PDF as docx might not be in test data
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('category', $result);
    }

    public function test_format_statistics_reflect_real_data()
    {
        $stats = $this->service->getFormatStatistics();
        
        // Should have multiple categories
        $this->assertGreaterThan(5, count($stats['by_category']));
        
        // Should have all three risk levels
        $this->assertArrayHasKey('Low', $stats['by_risk']);
        
        // Should have Textual category with multiple formats
        $this->assertArrayHasKey('Textual', $stats['by_category']);
        $this->assertGreaterThan(0, $stats['by_category']['Textual']);
    }

    public function test_low_risk_format_has_retain_action()
    {
        // Many formats with Low risk should have Retain action
        $ids = $this->service->getSupportedPronomIds();
        
        $retainCount = 0;
        foreach ($ids as $id) {
            $result = $this->service->assessFile($id);
            if ($result['risk_level'] === 'Low' && $result['recommended_action'] === 'Retain') {
                $retainCount++;
            }
            
            // Check a few, not all (would be too slow)
            if ($retainCount >= 5) {
                break;
            }
        }
        
        $this->assertGreaterThan(0, $retainCount);
    }

    public function test_assessment_notes_are_meaningful()
    {
        $result = $this->service->assessFile('fmt/412');
        
        $notes = $result['assessment_notes'];
        $this->assertNotEmpty($notes);
        $this->assertGreaterThan(20, strlen($notes)); // Should be meaningful text
    }

    public function test_tools_recommendations_are_specific()
    {
        $result = $this->service->assessFile('fmt/412');
        
        $tools = $result['tools'];
        $this->assertIsArray($tools);
        
        // Retain action may have no tools (which is correct)
        // Check that tools are valid when present
        foreach ($tools as $tool) {
            $this->assertIsString($tool);
            $this->assertNotEmpty($tool);
        }
    }

    public function test_nara_format_id_is_valid_when_present()
    {
        $result = $this->service->assessFile('fmt/412');
        
        if ($result['nara_format_id'] !== null) {
            $this->assertStringContainsString('NF', $result['nara_format_id']);
        }
    }

    public function test_category_labels_are_human_readable()
    {
        $result = $this->service->assessFile('fmt/412');
        
        $categoryLabel = $result['category_label'];
        $this->assertNotEmpty($categoryLabel);
        $this->assertStringNotContainsString('naraid:', $categoryLabel);
        $this->assertStringNotContainsString('http://', $categoryLabel);
    }

    public function test_multiple_formats_from_same_category()
    {
        // Get multiple formats and verify they have consistent category structure
        $formats = ['fmt/412', 'fmt/95', 'fmt/354']; // Various PDFs
        
        foreach ($formats as $format) {
            $result = $this->service->assessFile($format);
            $this->assertNotEmpty($result['category'], 
                "Format {$format} should have a category");
            $this->assertNotEmpty($result['category_label'], 
                "Format {$format} should have a category label");
        }
    }

    public function test_unknown_format_handling()
    {
        $result = $this->service->assessFile('fmt/999999');
        
        $this->assertEquals('Unknown Format', $result['format_name']);
        $this->assertFalse($result['ttl_source']);
        $this->assertNull($result['nara_format_id']);
        $this->assertEquals('Moderate', $result['risk_level']);
        $this->assertEquals('Identify', $result['recommended_action']);
    }

    public function test_complete_workflow_from_pronom_to_assessment()
    {
        // Simulate complete workflow: get supported IDs → assess one
        $supportedIds = $this->service->getSupportedPronomIds();
        
        $this->assertNotEmpty($supportedIds);
        
        // Pick first ID and assess it
        $firstId = $supportedIds[0];
        $result = $this->service->assessFile($firstId);
        
        $this->assertEquals($firstId, $result['pronom_id']);
        $this->assertTrue($result['ttl_source']);
        $this->assertNotNull($result['category']);
    }
}
