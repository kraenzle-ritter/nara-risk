<?php

namespace KraenzleRitter\NaraRiskAssessment\Tests;

use KraenzleRitter\NaraRiskAssessment\Services\NaraTtlDownloadService;
use Illuminate\Support\Facades\Storage;

class NaraTtlDownloadServiceTest extends TestCase
{
    private NaraTtlDownloadService $service;
    private string $cachePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NaraTtlDownloadService();
        $this->cachePath = storage_path('app/nara');
    }

    public function test_cache_directory_is_created()
    {
        $this->assertDirectoryExists($this->cachePath);
        $this->assertTrue(is_writable($this->cachePath));
    }

    public function test_cache_directory_has_correct_permissions()
    {
        $perms = substr(sprintf('%o', fileperms($this->cachePath)), -4);
        $this->assertEquals('0755', $perms);
    }

    public function test_get_ttl_content_returns_string()
    {
        // This will either use cache or download
        $content = $this->service->getTtlContent();
        
        $this->assertIsString($content);
        $this->assertNotEmpty($content);
        $this->assertStringContainsString('@prefix', $content);
        $this->assertStringContainsString('fileformats', $content);
    }

    public function test_ttl_content_is_valid_rdf_format()
    {
        $content = $this->service->getTtlContent();
        
        // Check for RDF/TTL format markers
        $this->assertStringContainsString('@prefix', $content);
        $this->assertStringContainsString('rdf:', $content);
        $this->assertStringContainsString('rdfs:', $content);
    }

    public function test_cache_file_is_created()
    {
        $this->service->getTtlContent();
        $cacheFile = $this->cachePath . '/nara_fileformats.ttl';
        
        $this->assertFileExists($cacheFile);
    }

    public function test_cache_file_has_reasonable_size()
    {
        $this->service->getTtlContent();
        $cacheFile = $this->cachePath . '/nara_fileformats.ttl';
        
        $size = filesize($cacheFile);
        
        // NARA TTL file should be at least 100KB
        $this->assertGreaterThan(100000, $size, 'TTL file should be at least 100KB');
        
        // But less than 10MB (sanity check)
        $this->assertLessThan(10000000, $size, 'TTL file should be less than 10MB');
    }

    public function test_get_cache_info_returns_array()
    {
        $info = $this->service->getCacheInfo();
        
        $this->assertIsArray($info);
        $this->assertArrayHasKey('exists', $info);
        $this->assertArrayHasKey('path', $info);
    }

    public function test_cache_info_contains_valid_data_when_cached()
    {
        // Ensure file is cached
        $this->service->getTtlContent();
        
        $info = $this->service->getCacheInfo();
        
        $this->assertTrue($info['exists']);
        $this->assertArrayHasKey('modified', $info);
        $this->assertArrayHasKey('age_days', $info);
        $this->assertArrayHasKey('valid', $info);
        $this->assertIsNumeric($info['age_days']);
    }

    public function test_refresh_cache_downloads_fresh_content()
    {
        // Get cached version first
        $this->service->getTtlContent();
        $cacheFile = $this->cachePath . '/nara_fileformats.ttl';
        $firstModTime = filemtime($cacheFile);
        
        // Wait a moment
        sleep(1);
        
        // Force refresh
        $content = $this->service->refreshCache();
        
        $secondModTime = filemtime($cacheFile);
        
        // File should have been re-downloaded (newer modification time)
        $this->assertGreaterThan($firstModTime, $secondModTime);
        $this->assertIsString($content);
        $this->assertNotEmpty($content);
    }

    public function test_refresh_cache_returns_valid_ttl_content()
    {
        $content = $this->service->refreshCache();
        
        $this->assertIsString($content);
        $this->assertNotEmpty($content);
        $this->assertStringContainsString('@prefix', $content);
    }

    public function test_download_all_schema_files_returns_results_array()
    {
        $results = $this->service->downloadAllSchemaFiles();
        
        $this->assertIsArray($results);
        $this->assertArrayHasKey('fileformats', $results);
        $this->assertArrayHasKey('category', $results);
        $this->assertArrayHasKey('risk', $results);
        $this->assertArrayHasKey('presaction', $results);
    }
}
