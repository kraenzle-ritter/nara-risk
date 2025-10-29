<?php

namespace KraenzleRitter\NaraRiskAssessment\Tests;

use KraenzleRitter\NaraRiskAssessment\Services\NaraAssessmentService;

class NaraAssessmentServiceTest extends TestCase
{
    private NaraAssessmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(NaraAssessmentService::class);
    }

    public function test_assess_file_returns_complete_result()
    {
        $result = $this->service->assessFile('fmt/412');

        $requiredFields = [
            'pronom_id', 'format_name', 'category', 'risk_level',
            'recommended_action', 'tools', 'nara_compliant', 'ttl_source',
        ];

        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $result);
        }

        $this->assertEquals('fmt/412', $result['pronom_id']);
        $this->assertIsBool($result['nara_compliant']);
        $this->assertIsArray($result['tools']);
    }

    public function test_unknown_format_returns_defaults()
    {
        $result = $this->service->assessFile('fmt/999999');

        $this->assertEquals('Unknown Format', $result['format_name']);
        $this->assertEquals('Moderate', $result['risk_level']);
        $this->assertEquals('Identify', $result['recommended_action']);
        $this->assertFalse($result['nara_compliant']);
        $this->assertFalse($result['ttl_source']);
    }

    public function test_statistics_and_supported_ids()
    {
        $ids = $this->service->getSupportedPronomIds();
        $stats = $this->service->getFormatStatistics();

        $this->assertIsArray($ids);
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_formats', $stats);
        $this->assertEquals(count($ids), $stats['total_formats']);
    }
}
