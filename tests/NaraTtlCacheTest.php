<?php

namespace KraenzleRitter\NaraRisk\Tests;

use KraenzleRitter\NaraRisk\Services\NaraAssessmentService;
use KraenzleRitter\NaraRisk\Services\NaraTtlDownloadService;
use KraenzleRitter\NaraRisk\Services\NaraTtlParserService;

/**
 * Download, failure memory and the parsed table, without network: the
 * "download" URL is a local file, which file_get_contents() reads as well.
 */
class NaraTtlCacheTest extends TestCase
{
    private string $cacheDir;
    private string $sample;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = sys_get_temp_dir() . '/nara-cache-test-' . uniqid();
        $this->sample = __DIR__ . '/fixtures/fileformats-sample.ttl';

        config([
            'nara.cache_directory' => $this->cacheDir,
            'nara.schema_urls.fileformats' => $this->sample,
            'nara.download' => true,
            'nara.retry_after_minutes' => 360,
        ]);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->cacheDir . '/*') ?: [] as $file) {
            unlink($file);
        }
        @rmdir($this->cacheDir);

        parent::tearDown();
    }

    public function test_downloads_from_the_configured_url_and_assesses()
    {
        $result = $this->assessment()->assessFile('fmt/978');

        $this->assertSame('Moderate', $result['risk_level']);
        $this->assertSame('Transform', $result['recommended_action']);
        $this->assertSame('NF00100', $result['nara_format_id']);
        $this->assertFileEquals($this->sample, $this->cacheFile());
        $this->assertFileExists($this->cacheFile() . '.parsed');
    }

    public function test_a_failed_download_is_not_retried_until_retry_after()
    {
        config(['nara.schema_urls.fileformats' => $this->cacheDir . '/missing.ttl']);

        $this->assertThrowsMessage('No TTL data available', fn () => (new NaraTtlDownloadService())->getTtlPath());
        $this->assertFileExists($this->cacheFile() . '.failed');

        // The source is back, but the failure is recent: no new attempt
        config(['nara.schema_urls.fileformats' => $this->sample]);
        $this->assertThrowsMessage('less than 360 minutes ago', fn () => (new NaraTtlDownloadService())->getTtlPath());
        $this->assertFileDoesNotExist($this->cacheFile());

        touch($this->cacheFile() . '.failed', time() - 361 * 60);

        $this->assertSame($this->cacheFile(), (new NaraTtlDownloadService())->getTtlPath());
        $this->assertFileExists($this->cacheFile());
        $this->assertFileDoesNotExist($this->cacheFile() . '.failed', 'a successful download clears the failure');
    }

    public function test_an_outdated_file_is_used_while_the_download_fails()
    {
        $this->outdatedCacheFile('# old' . "\n" . file_get_contents($this->sample));
        config(['nara.schema_urls.fileformats' => $this->cacheDir . '/missing.ttl']);

        $this->assertSame($this->cacheFile(), (new NaraTtlDownloadService())->getTtlPath());
        $this->assertFileExists($this->cacheFile() . '.failed');

        config(['nara.schema_urls.fileformats' => $this->sample]);
        (new NaraTtlDownloadService())->getTtlPath();

        $this->assertStringStartsWith('# old', file_get_contents($this->cacheFile()), 'not retried yet');
    }

    public function test_with_download_off_the_existing_file_is_used_however_old()
    {
        config(['nara.download' => false]);
        $this->outdatedCacheFile('# old' . "\n" . file_get_contents($this->sample));

        $this->assertSame('Moderate', $this->assessment()->assessFile('fmt/978')['risk_level']);
        $this->assertStringStartsWith('# old', file_get_contents($this->cacheFile()));
        $this->assertFileDoesNotExist($this->cacheFile() . '.failed');
    }

    public function test_with_download_off_and_no_file_it_fails_at_once()
    {
        config(['nara.download' => false]);

        $this->assertThrowsMessage('downloading is off', fn () => (new NaraTtlDownloadService())->getTtlPath());
        $this->assertFileDoesNotExist($this->cacheFile());
    }

    public function test_an_html_page_is_not_taken_for_ttl()
    {
        config(['nara.schema_urls.fileformats' => __DIR__ . '/fixtures/not-found.html']);

        $this->assertThrowsMessage('does not appear to be valid TTL', fn () => (new NaraTtlDownloadService())->getTtlPath());
        $this->assertFileDoesNotExist($this->cacheFile());
    }

    public function test_a_failed_refresh_keeps_the_old_file()
    {
        $service = new NaraTtlDownloadService();
        $service->getTtlPath();
        config(['nara.schema_urls.fileformats' => $this->cacheDir . '/missing.ttl']);

        $content = (new NaraTtlDownloadService())->refreshCache();

        $this->assertSame(file_get_contents($this->sample), $content);
        $this->assertFileExists($this->cacheFile());
    }

    public function test_the_parsed_table_is_reused_until_the_file_changes()
    {
        $ttlFile = (new NaraTtlDownloadService())->getTtlPath();
        $parser = new NaraTtlParserService();
        $mappings = $parser->parseFile($ttlFile);
        $this->assertArrayHasKey('fmt/978', $mappings);

        // Mark the stored table: a second parse that returns the mark did not parse
        $parsed = unserialize(file_get_contents($ttlFile . '.parsed'));
        $parsed['mappings']['fmt/978']['format_name'] = 'from the parsed table';
        file_put_contents($ttlFile . '.parsed', serialize($parsed));

        $this->assertSame('from the parsed table', (new NaraTtlParserService())->parseFile($ttlFile)['fmt/978']['format_name']);

        file_put_contents($ttlFile, "# changed\n", FILE_APPEND);

        $this->assertSame('NF00100', (new NaraTtlParserService())->parseFile($ttlFile)['fmt/978']['format_name']);
    }

    public function test_a_parse_failure_is_not_stored()
    {
        $ttlFile = $this->cacheDir . '/broken.ttl';
        mkdir($this->cacheDir, 0o755, true);
        file_put_contents($ttlFile, 'This is not valid TTL content');

        $this->assertSame([], (new NaraTtlParserService())->parseFile($ttlFile));
        $this->assertFileDoesNotExist($ttlFile . '.parsed');
    }

    private function assessment(): NaraAssessmentService
    {
        return new NaraAssessmentService(new NaraTtlDownloadService(), new NaraTtlParserService());
    }

    private function cacheFile(): string
    {
        return $this->cacheDir . '/nara_fileformats.ttl';
    }

    private function outdatedCacheFile(string $content): void
    {
        if (! is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0o755, true);
        }
        file_put_contents($this->cacheFile(), $content);
        touch($this->cacheFile(), time() - 30 * 86400);
    }

    private function assertThrowsMessage(string $message, callable $call): void
    {
        try {
            $call();
        } catch (\Exception $e) {
            $this->assertStringContainsString($message, $e->getMessage());

            return;
        }

        $this->fail('Expected an exception containing: ' . $message);
    }
}
