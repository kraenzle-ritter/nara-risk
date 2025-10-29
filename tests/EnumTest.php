<?php

namespace KraenzleRitter\NaraRiskAssessment\Tests;

use KraenzleRitter\NaraRiskAssessment\Enums\Category;
use KraenzleRitter\NaraRiskAssessment\Enums\RiskLevel;
use KraenzleRitter\NaraRiskAssessment\Enums\PreservationAction;
use PHPUnit\Framework\TestCase;

class EnumTest extends TestCase
{
    public function test_category_enum_has_correct_values()
    {
        $this->assertEquals('Audio', Category::AUDIO->value);
        $this->assertEquals('Textual', Category::TEXTUAL->value);
        $this->assertEquals('Video', Category::VIDEO->value);
        $this->assertEquals('StillImage', Category::STILL_IMAGE->value);
    }

    public function test_category_enum_has_correct_labels()
    {
        $this->assertEquals('Digital Audio', Category::AUDIO->getLabel());
        $this->assertEquals('Textual and Word Processing', Category::TEXTUAL->getLabel());
        $this->assertEquals('Digital Video', Category::VIDEO->getLabel());
        $this->assertEquals('Digital Still Image', Category::STILL_IMAGE->getLabel());
    }

    public function test_category_enum_has_correct_nara_ids()
    {
        $this->assertEquals('naraid:Audio', Category::AUDIO->getNaraId());
        $this->assertEquals('naraid:Textual', Category::TEXTUAL->getNaraId());
        $this->assertEquals('naraid:StillImage', Category::STILL_IMAGE->getNaraId());
    }

    public function test_category_enum_has_all_16_categories()
    {
        $cases = Category::cases();
        $this->assertCount(16, $cases);
        
        $expectedCategories = [
            'AUDIO', 'CALENDARS', 'CINEMA', 'CODE', 'DATABASES', 
            'DESIGN_VECTOR', 'EMAIL', 'GEOSPATIAL', 'NAV_CHARTS', 
            'PRESENTATION', 'SPREADSHEETS', 'STILL_IMAGE', 
            'STRUCTURED_DATA', 'TEXTUAL', 'VIDEO', 'WEB'
        ];
        
        $actualCategories = array_map(fn($case) => $case->name, $cases);
        $this->assertEquals($expectedCategories, $actualCategories);
    }

    public function test_category_pronom_patterns_return_arrays()
    {
        // Test a few categories have PRONOM patterns
        $audioPatterns = Category::AUDIO->getPronomPatterns();
        $this->assertIsArray($audioPatterns);
        $this->assertNotEmpty($audioPatterns);
        $this->assertContains('fmt/1', $audioPatterns); // WAV
        
        $textualPatterns = Category::TEXTUAL->getPronomPatterns();
        $this->assertIsArray($textualPatterns);
        $this->assertContains('fmt/412', $textualPatterns); // PDF 1.7
        $this->assertContains('fmt/95', $textualPatterns); // PDF 1.4
    }

    public function test_risk_level_enum_has_correct_values()
    {
        $this->assertEquals('Low', RiskLevel::LOW->value);
        $this->assertEquals('Moderate', RiskLevel::MODERATE->value);
        $this->assertEquals('High', RiskLevel::HIGH->value);
    }

    public function test_risk_level_enum_has_correct_labels()
    {
        $this->assertEquals('Low Risk', RiskLevel::LOW->getLabel());
        $this->assertEquals('Moderate Risk', RiskLevel::MODERATE->getLabel());
        $this->assertEquals('High Risk', RiskLevel::HIGH->getLabel());
    }

    public function test_risk_level_enum_has_correct_nara_ids()
    {
        $this->assertEquals('naraid:Low', RiskLevel::LOW->getNaraId());
        $this->assertEquals('naraid:Moderate', RiskLevel::MODERATE->getNaraId());
        $this->assertEquals('naraid:High', RiskLevel::HIGH->getNaraId());
    }

    public function test_risk_level_enum_has_all_3_levels()
    {
        $cases = RiskLevel::cases();
        $this->assertCount(3, $cases);
    }

    public function test_preservation_action_enum_has_correct_values()
    {
        $this->assertEquals('Retain', PreservationAction::RETAIN->value);
        $this->assertEquals('Transform', PreservationAction::TRANSFORM->value);
        $this->assertEquals('Identify', PreservationAction::IDENTIFY->value);
        $this->assertEquals('Assess', PreservationAction::ASSESS->value);
    }

    public function test_preservation_action_enum_has_correct_labels()
    {
        $this->assertEquals('Retain', PreservationAction::RETAIN->getLabel());
        $this->assertEquals('Transform', PreservationAction::TRANSFORM->getLabel());
        $this->assertEquals('Identify Version', PreservationAction::IDENTIFY->getLabel());
        $this->assertEquals('Retain for Future Assessment', PreservationAction::ASSESS->getLabel());
    }

    public function test_preservation_action_enum_has_correct_nara_ids()
    {
        $this->assertEquals('naraid:Retain', PreservationAction::RETAIN->getNaraId());
        $this->assertEquals('naraid:Transform', PreservationAction::TRANSFORM->getNaraId());
        $this->assertEquals('naraid:Identify', PreservationAction::IDENTIFY->getNaraId());
        $this->assertEquals('naraid:Assess', PreservationAction::ASSESS->getNaraId());
    }

    public function test_preservation_action_enum_has_all_4_actions()
    {
        $cases = PreservationAction::cases();
        $this->assertCount(4, $cases);
    }
}
