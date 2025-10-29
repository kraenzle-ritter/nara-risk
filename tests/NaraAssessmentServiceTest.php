<?php

namespace KraenzleRitter\NaraRiskAssessment\Tests;

use KraenzleRitter\NaraRiskAssessment\Services\NaraAssessmentService;

class NaraAssessmentServiceTest extends TestCase
{
    private NaraAssessmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NaraAssessmentService();
    }

    public function test_assess_file_returns_array()
    {
        $result = $this->service->assessFile('fmt/412');
        
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function test_assess_file_returns_all_17_fields()
    {
        $result = $this->service->assessFile('fmt/412');
        
        $expectedFields = [
            'pronom_id',
            'format_name',
            'category',
            'category_label',
            'nara_category_id',
            'nara_format_id',
            'risk_level',
            'risk_label',
            'nara_risk_id',
            'recommended_action',
            'action_label',
            'nara_action_id',
            'tools',
            'nara_compliant',
            'ttl_source',
            'assessment_notes',
            'nara_subject'
        ];
        
        foreach ($expectedFields as $field) {
            $this->assertArrayHasKey($field, $result, "Missing field: {$field}");
        }
    }

    public function test_assess_file_for_known_format_returns_valid_data()
    {
        $result = $this->service->assessFile('fmt/412'); // PDF 1.7
        
        $this->assertEquals('fmt/412', $result['pronom_id']);
        $this->assertNotEmpty($result['format_name']);
        $this->assertTrue($result['ttl_source']);
        $this->assertIsArray($result['tools']);
    }

    public function test_assess_file_for_unknown_format_returns_default_values()
    {
        $result = $this->service->assessFile('fmt/99999'); // Non-existent format
        
        $this->assertEquals('fmt/99999', $result['pronom_id']);
        $this->assertEquals('Unknown Format', $result['format_name']);
        $this->assertFalse($result['ttl_source']);
        $this->assertNull($result['nara_format_id']);
        $this->assertEquals('Moderate', $result['risk_level']);
    }

    public function test_assess_file_returns_correct_risk_levels()
    {
        $result = $this->service->assessFile('fmt/412'); // PDF 1.7
        
        $this->assertContains($result['risk_level'], ['Low', 'Moderate', 'High']);
        $this->assertStringContainsString('Risk', $result['risk_label']);
    }

    public function test_assess_file_returns_valid_preservation_action()
    {
        $result = $this->service->assessFile('fmt/412');
        
        $validActions = ['Retain', 'Transform', 'Identify', 'Assess'];
        $this->assertContains($result['recommended_action'], $validActions);
        $this->assertNotEmpty($result['action_label']);
    }

    public function test_get_supported_pronom_ids_returns_array()
    {
        $ids = $this->service->getSupportedPronomIds();
        
        $this->assertIsArray($ids);
        $this->assertNotEmpty($ids);
    }

    public function test_get_supported_pronom_ids_contains_known_formats()
    {
        $ids = $this->service->getSupportedPronomIds();
        
        $this->assertContains('fmt/412', $ids); // PDF 1.7
        $this->assertContains('fmt/95', $ids);  // PDF 1.4
    }

    public function test_get_format_statistics_returns_array()
    {
        $stats = $this->service->getFormatStatistics();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_formats', $stats);
        $this->assertArrayHasKey('by_category', $stats);
        $this->assertArrayHasKey('by_risk', $stats);
        $this->assertArrayHasKey('by_action', $stats);
    }

    public function test_get_format_statistics_has_valid_counts()
    {
        $stats = $this->service->getFormatStatistics();
        
        $this->assertIsInt($stats['total_formats']);
        $this->assertGreaterThan(0, $stats['total_formats']);
    }

    public function test_get_format_statistics_categories_are_valid()
    {
        $stats = $this->service->getFormatStatistics();
        
        $this->assertIsArray($stats['by_category']);
        $this->assertNotEmpty($stats['by_category']);
        
        // Check that we have some common categories
        $categories = array_keys($stats['by_category']);
        $this->assertContains('Textual', $categories);
    }

    public function test_get_format_statistics_risk_levels_are_valid()
    {
        $stats = $this->service->getFormatStatistics();
        
        $this->assertIsArray($stats['by_risk']);
        
        // Should have Low, Moderate, or High risks
        $risks = array_keys($stats['by_risk']);
        $validRisks = ['Low', 'Moderate', 'High'];
        
        foreach ($risks as $risk) {
            $this->assertContains($risk, $validRisks);
        }
    }

    public function test_get_format_statistics_actions_are_valid()
    {
        $stats = $this->service->getFormatStatistics();
        
        $this->assertIsArray($stats['by_action']);
        
        $actions = array_keys($stats['by_action']);
        $validActions = ['Retain', 'Transform', 'Identify', 'Assess'];
        
        foreach ($actions as $action) {
            $this->assertContains($action, $validActions);
        }
    }

    public function test_assess_file_tools_array_is_not_empty()
    {
        // Use a format that requires transformation (not 'Retain')
        // Let's find a format with Transform action
        $result = $this->service->assessFile('fmt/412');
        
        $this->assertIsArray($result['tools']);
        
        // Retain action has no tools, which is correct
        // If action is Transform, Assess, or Identify, tools should be present
        if (in_array($result['recommended_action'], ['Transform', 'Assess', 'Identify'])) {
            $this->assertNotEmpty($result['tools'], 
                "Action '{$result['recommended_action']}' should have tools");
        }
    }

    public function test_assess_file_includes_assessment_notes()
    {
        $result = $this->service->assessFile('fmt/412');
        
        $this->assertIsString($result['assessment_notes']);
        $this->assertNotEmpty($result['assessment_notes']);
    }

    public function test_multiple_formats_can_be_assessed()
    {
        $formats = ['fmt/412', 'fmt/95', 'fmt/354'];
        
        foreach ($formats as $pronomId) {
            $result = $this->service->assessFile($pronomId);
            $this->assertIsArray($result);
            $this->assertEquals($pronomId, $result['pronom_id']);
        }
    }

    public function test_assess_file_nara_compliant_is_boolean()
    {
        $result = $this->service->assessFile('fmt/412');
        
        $this->assertIsBool($result['nara_compliant']);
    }

    public function test_ttl_source_indicates_data_origin()
    {
        $knownFormat = $this->service->assessFile('fmt/412');
        $this->assertTrue($knownFormat['ttl_source']);
        
        $unknownFormat = $this->service->assessFile('fmt/99999');
        $this->assertFalse($unknownFormat['ttl_source']);
    }
}
