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
        $this->service = $this->app->make(NaraAssessmentService::class);
    }

    public function test_format_assessment_with_real_data()
    {
        $result = $this->service->assessFile('fmt/412'); // PDF 1.7

        $this->assertEquals('fmt/412', $result['pronom_id']);
        $this->assertNotEmpty($result['format_name']);
        $this->assertIsString($result['category']);
        $this->assertIsBool($result['nara_compliant']);
        $this->assertIsBool($result['ttl_source']);
        $this->assertIsArray($result['tools']);
    }

    public function test_unknown_format_handling()
    {
        $result = $this->service->assessFile('fmt/999999');

        $this->assertEquals('Unknown Format', $result['format_name']);
        $this->assertFalse($result['ttl_source']);
        $this->assertEquals('Moderate', $result['risk_level']);
        $this->assertEquals('Identify', $result['recommended_action']);
    }

    public function test_format_statistics()
    {
        $stats = $this->service->getFormatStatistics();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_formats', $stats);
        $this->assertArrayHasKey('by_category', $stats);
        $this->assertArrayHasKey('by_risk', $stats);
        $this->assertArrayHasKey('by_action', $stats);
    }

    public function test_supported_pronom_ids()
    {
        $ids = $this->service->getSupportedPronomIds();

        $this->assertIsArray($ids);
        $this->assertNotEmpty($ids);

        // Test first ID works
        $result = $this->service->assessFile($ids[0]);
        $this->assertTrue($result['ttl_source']);
    }
}
