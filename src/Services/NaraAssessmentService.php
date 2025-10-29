<?php

namespace KraenzleRitter\NaraRiskAssessment\Services;

use Illuminate\Support\Facades\Log;
use KraenzleRitter\NaraRiskAssessment\Enums\Category;
use KraenzleRitter\NaraRiskAssessment\Enums\PreservationAction;
use KraenzleRitter\NaraRiskAssessment\Enums\RiskLevel;

/**
 * NARA Digital Preservation Framework Assessment Service
 * Based on official NARA TTL/RDF data from https://www.archives.gov/files/lod/dpframework/fileformats.ttl
 */
class NaraAssessmentService
{
    private const LARGE_FILE_THRESHOLD = 100 * 1024 * 1024; // 100MB
    private const DEFAULT_RISK_LEVEL = 'Moderate';
    private const DEFAULT_ACTION = 'Identify';

    private array $ttlMappings = [];
    private bool $initialized = false;

    public function __construct(
        private readonly NaraTtlDownloadService $downloadService,
        private readonly NaraTtlParserService $parserService
    ) {
    }

    /**
     * Initialize service by loading TTL data
     */
    private function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        try {
            // Get TTL content (cached or downloaded)
            $ttlContent = $this->downloadService->getTtlContent();

            // Parse TTL and extract PRONOM mappings
            $this->ttlMappings = $this->parserService->parseTtl($ttlContent);

            Log::info('NARA Assessment Service initialized', [
                'ttl_mappings' => count($this->ttlMappings),
            ]);

            $this->initialized = true;

        } catch (\Exception $e) {
            Log::error('Failed to load NARA TTL data - service unavailable', [
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('NARA TTL data unavailable: ' . $e->getMessage());
        }
    }

    /**
     * Assess preservation risk for a file based on PRONOM ID using TTL data
     */
    public function assessFile(string $pronomId, array $fileData = []): array
    {
        $this->initialize();

        // Look up in TTL mappings
        $ttlMapping = $this->ttlMappings[$pronomId] ?? null;

        if ($ttlMapping) {
            return $this->processNaraTtlMapping($pronomId, $ttlMapping, $fileData);
        }

        // Unknown format - not in NARA registry
        return $this->buildUnknownFormatResult($pronomId);
    }

    /**
     * Process NARA TTL mapping data
     */
    private function processNaraTtlMapping(string $pronomId, array $ttlMapping, array $fileData): array
    {
        // Extract NARA properties from TTL mapping
        $category = $ttlMapping['nara_category']['label'] ?? 'Unknown';
        $categoryId = $ttlMapping['nara_category']['nara_id'] ?? null;

        $riskLevel = $ttlMapping['nara_risk_level']['value'] ?? 'Moderate';
        $riskLabel = $ttlMapping['nara_risk_level']['label'] ?? 'Moderate Risk';
        $riskId = $ttlMapping['nara_risk_level']['nara_id'] ?? null;

        $action = $ttlMapping['nara_preservation_action']['value'] ?? 'Assess';
        $actionLabel = $ttlMapping['nara_preservation_action']['label'] ?? 'Retain for Future Assessment';
        $actionId = $ttlMapping['nara_preservation_action']['nara_id'] ?? null;

        // Extract NARA File Format ID from subject (e.g., "naraid:NF00100" -> "NF00100")
        $naraFormatId = null;
        if (isset($ttlMapping['subject'])) {
            $subject = $ttlMapping['subject'];
            if (preg_match('/NF\d+/', $subject, $matches)) {
                $naraFormatId = $matches[0];
            }
        }

        // Get tools for the action
        $tools = $this->getToolsForAction($action);

        return [
            'pronom_id' => $pronomId,
            'format_name' => $ttlMapping['format_name'],
            'category' => $category,
            'category_label' => $category,
            'nara_category_id' => $categoryId,
            'nara_format_id' => $naraFormatId,
            'risk_level' => $riskLevel,
            'risk_label' => $riskLabel,
            'nara_risk_id' => $riskId,
            'recommended_action' => $action,
            'action_label' => $actionLabel,
            'nara_action_id' => $actionId,
            'tools' => $tools,
            'nara_compliant' => true,
            'ttl_source' => true,
            'assessment_notes' => $this->generateTtlAssessmentNotes($ttlMapping, $fileData),
            'nara_subject' => $ttlMapping['subject'],
        ];
    }

    /**
     * Get tools for preservation action
     */
    private function getToolsForAction(string $action): array
    {
        return match(strtolower($action)) {
            'transform' => ['pandoc', 'ffmpeg', 'imagemagick', 'libreoffice', 'fits'],
            'assess' => ['jhove', 'fits', 'droid', 'verapdf'],
            'identify' => ['siegfried', 'file', 'trid', 'droid'],
            'retain' => [],
            default => []
        };
    }

    /**
     * Generate assessment notes from TTL data
     */
    private function generateTtlAssessmentNotes(array $ttlMapping, array $fileData): string
    {
        $notes = [];

        $formatName = $ttlMapping['format_name'] ?? 'Unknown Format';
        $notes[] = "Format '{$formatName}' assessed using official NARA/DPLA ontology data.";

        if (isset($ttlMapping['nara_category']['label'])) {
            $notes[] = "Categorized as {$ttlMapping['nara_category']['label']}.";
        }

        if (isset($ttlMapping['nara_risk_level']['label'])) {
            $notes[] = "Risk level: {$ttlMapping['nara_risk_level']['label']}.";
        }

        if (isset($ttlMapping['nara_preservation_action']['label'])) {
            $notes[] = "Recommended action: {$ttlMapping['nara_preservation_action']['label']}.";
        }

        // File size considerations
        if (isset($fileData['filesize']) && $fileData['filesize'] > self::LARGE_FILE_THRESHOLD) {
            $notes[] = "Large file size noted for preservation planning.";
        }

        return implode(' ', $notes);
    }

    /**
     * Get all supported PRONOM IDs from NARA TTL data
     */
    public function getSupportedPronomIds(): array
    {
        $this->initialize();

        return array_keys($this->ttlMappings);
    }

    /**
     * Get statistics for supported formats from NARA TTL data
     */
    public function getFormatStatistics(): array
    {
        $this->initialize();

        $stats = [
            'total_formats' => count($this->ttlMappings),
            'by_category' => [],
            'by_risk' => [],
            'by_action' => [],
        ];

        foreach ($this->ttlMappings as $mapping) {
            $category = $mapping['nara_category']['value'] ?? 'Unknown';
            $risk = $mapping['nara_risk_level']['value'] ?? 'Moderate';
            $action = $mapping['nara_preservation_action']['value'] ?? 'Assess';

            $stats['by_category'][$category] = ($stats['by_category'][$category] ?? 0) + 1;
            $stats['by_risk'][$risk] = ($stats['by_risk'][$risk] ?? 0) + 1;
            $stats['by_action'][$action] = ($stats['by_action'][$action] ?? 0) + 1;
        }

        return $stats;
    }

    /**
     * Get TTL cache information
     */
    public function getCacheInfo(): array
    {
        return $this->downloadService->getCacheInfo();
    }

    /**
     * Get TTL parsing statistics
     */
    public function getParsingStatistics(): array
    {
        $this->initialize();

        return $this->parserService->getStatistics();
    }

    /**
     * Build result array for unknown formats
     */
    private function buildUnknownFormatResult(string $pronomId): array
    {
        return [
            'pronom_id' => $pronomId,
            'format_name' => 'Unknown Format',
            'category' => null,
            'category_label' => 'Unknown',
            'nara_category_id' => null,
            'nara_format_id' => null,
            'risk_level' => self::DEFAULT_RISK_LEVEL,
            'risk_label' => 'Moderate Risk',
            'nara_risk_id' => null,
            'recommended_action' => self::DEFAULT_ACTION,
            'action_label' => 'Identify Version',
            'nara_action_id' => null,
            'tools' => $this->getToolsForAction(self::DEFAULT_ACTION),
            'nara_compliant' => false,
            'ttl_source' => false,
            'assessment_notes' => 'Format not recognized in NARA framework - requires identification and assessment',
            'nara_subject' => null,
        ];
    }
}
