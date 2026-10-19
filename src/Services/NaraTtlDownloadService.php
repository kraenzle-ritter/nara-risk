<?php

namespace KraenzleRitter\NaraRisk\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * NARA TTL File Download and Caching Service
 *
 * Settings come from config/nara.php. On a server without internet access a
 * download waits for the full timeout and fails; a failure is therefore
 * remembered and not retried for `retry_after_minutes`, and `download` false
 * turns downloading off altogether.
 */
class NaraTtlDownloadService
{
    private const SCHEMA_URLS = [
        'fileformats' => 'https://www.archives.gov/files/lod/dpframework/fileformats.ttl',
        'presaction' => 'https://www.archives.gov/files/lod/dpframework/presaction.ttl',
        'category' => 'https://www.archives.gov/files/lod/dpframework/category.ttl',
        'risk' => 'https://www.archives.gov/files/lod/dpframework/risk.ttl',
        'schema' => 'https://www.archives.gov/files/lod/dpframework/dpframeworkschema.ttl',
    ];

    private const CACHE_FILENAME = 'nara_fileformats.ttl';
    private const FAILURE_MARKER = 'nara_fileformats.ttl.failed';
    private const CACHE_DAYS = 28; // 4 weeks

    private string $cachePath;

    public function __construct()
    {
        $this->cachePath = rtrim((string) config('nara.cache_directory', storage_path('app/nara')), '/');
        if (! is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0o755, true);
        }
    }

    /**
     * Get TTL content - download if needed or use cache
     */
    public function getTtlContent(): string
    {
        return file_get_contents($this->getTtlPath());
    }

    /**
     * Path of the fileformats TTL file: downloaded if missing or older than
     * the cache duration, else the cached file.
     *
     * When downloading is off or failed recently, an outdated file is used
     * without trying; with no file at all this throws at once.
     */
    public function getTtlPath(): string
    {
        $cacheFile = $this->cacheFile();

        if (file_exists($cacheFile) && $this->isCacheValid($cacheFile)) {
            return $cacheFile;
        }

        if (! $this->downloadEnabled() || $this->failedRecently()) {
            if (file_exists($cacheFile)) {
                return $cacheFile;
            }

            throw new \Exception($this->downloadEnabled()
                ? 'No TTL data available: the last download failed less than ' . $this->retryAfterMinutes() . ' minutes ago'
                : 'No TTL data available: downloading is off (nara.download) and ' . $cacheFile . ' is missing');
        }

        $this->downloadAndCache();

        return $cacheFile;
    }

    /**
     * Download all NARA schema files
     */
    public function downloadAllSchemaFiles(): array
    {
        $results = [];

        foreach ($this->schemaUrls() as $name => $url) {
            try {
                $filename = "nara_{$name}.ttl";
                $content = $this->downloadFile($url, $filename);
                $results[$name] = [
                    'success' => true,
                    'filename' => $filename,
                    'size' => strlen($content),
                ];
            } catch (\Exception $e) {
                $results[$name] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
                Log::error("Failed to download NARA {$name} file", ['error' => $e->getMessage()]);
            }
        }

        return $results;
    }

    /**
     * Download a single TTL file and save to cache
     */
    private function downloadFile(string $url, string $filename, bool $force = false): string
    {
        $cacheFile = $this->cachePath . '/' . $filename;

        // Check if file exists and is recent
        if (! $force && file_exists($cacheFile) && $this->isCacheValid($cacheFile)) {
            return file_get_contents($cacheFile);
        }

        Log::info('Downloading NARA TTL file', ['url' => $url]);

        $context = stream_context_create([
            'http' => [
                'timeout' => (float) config('nara.timeout', 60),
                'user_agent' => 'AgateSipLine/1.0 (Digital Preservation Tool)',
                'follow_location' => true,
                'max_redirects' => 5,
            ],
        ]);

        $ttlContent = @file_get_contents($url, false, $context);

        if ($ttlContent === false) {
            throw new \Exception('Failed to download TTL file from ' . $url);
        }

        // A moved file may come back as an HTML page («Page Not Found»)
        if (preg_match('/^\s*</', $ttlContent) === 1 || ! str_contains($ttlContent, '@prefix')) {
            throw new \Exception('Downloaded content from ' . $url . ' does not appear to be valid TTL');
        }

        // Write next to the file and rename, so a parallel reader never sees half of it
        $tmpFile = $cacheFile . '.' . getmypid() . '.tmp';
        if (file_put_contents($tmpFile, $ttlContent) === false || ! rename($tmpFile, $cacheFile)) {
            @unlink($tmpFile);

            throw new \Exception('Failed to write TTL cache file');
        }

        Log::info('Successfully downloaded and cached NARA TTL file', [
            'file' => $cacheFile,
            'size' => strlen($ttlContent),
        ]);

        return $ttlContent;
    }

    /**
     * Check if cache file is valid (less than 4 weeks old)
     */
    private function isCacheValid(string $cacheFile): bool
    {
        $fileTime = filemtime($cacheFile);
        $expiryTime = Carbon::now()->subDays($this->cacheDays())->timestamp;

        return $fileTime > $expiryTime;
    }

    /**
     * Download TTL file and save to cache
     */
    private function downloadAndCache(bool $force = false): string
    {
        $cacheFile = $this->cacheFile();
        $url = $this->schemaUrls()['fileformats'];

        try {
            $ttlContent = $this->downloadFile($url, self::CACHE_FILENAME, $force);
            @unlink($this->failureMarker());

            return $ttlContent;

        } catch (\Exception $e) {
            @touch($this->failureMarker());

            Log::error('Failed to download NARA TTL file', [
                'error' => $e->getMessage(),
                'url' => $url,
                'retry_after_minutes' => $this->retryAfterMinutes(),
            ]);

            // Try to use existing cache even if old
            if (file_exists($cacheFile)) {
                Log::warning('Using old cached TTL file due to download failure');

                return file_get_contents($cacheFile);
            }

            throw new \Exception('No TTL data available: ' . $e->getMessage());
        }
    }

    /**
     * Get cache file information
     */
    public function getCacheInfo(): array
    {
        $cacheFile = $this->cacheFile();
        $lastFailure = file_exists($this->failureMarker())
            ? Carbon::createFromTimestamp(filemtime($this->failureMarker()))->toISOString()
            : null;

        if (! file_exists($cacheFile)) {
            return [
                'exists' => false,
                'path' => $cacheFile,
                'download' => $this->downloadEnabled(),
                'last_failure' => $lastFailure,
            ];
        }

        $fileTime = filemtime($cacheFile);
        $isValid = $this->isCacheValid($cacheFile);

        return [
            'exists' => true,
            'path' => $cacheFile,
            'size' => filesize($cacheFile),
            'modified' => Carbon::createFromTimestamp($fileTime)->toISOString(),
            'valid' => $isValid,
            'expires' => Carbon::createFromTimestamp($fileTime)->addDays($this->cacheDays())->toISOString(),
            'age_days' => Carbon::now()->diffInDays(Carbon::createFromTimestamp($fileTime)),
            'download' => $this->downloadEnabled(),
            'last_failure' => $lastFailure,
        ];
    }

    /**
     * Force refresh of TTL cache
     *
     * The old file is kept until the new one has arrived: a failed refresh
     * must not leave the assessment without data.
     */
    public function refreshCache(): string
    {
        return $this->downloadAndCache(force: true);
    }

    private function cacheFile(): string
    {
        return $this->cachePath . '/' . self::CACHE_FILENAME;
    }

    private function failureMarker(): string
    {
        return $this->cachePath . '/' . self::FAILURE_MARKER;
    }

    private function failedRecently(): bool
    {
        $marker = $this->failureMarker();

        return file_exists($marker)
            && filemtime($marker) > Carbon::now()->subMinutes($this->retryAfterMinutes())->timestamp;
    }

    private function downloadEnabled(): bool
    {
        return (bool) config('nara.download', true);
    }

    private function retryAfterMinutes(): int
    {
        return (int) config('nara.retry_after_minutes', 360);
    }

    private function cacheDays(): int
    {
        return (int) config('nara.cache_days', self::CACHE_DAYS);
    }

    /**
     * @return array<string, string>
     */
    private function schemaUrls(): array
    {
        return (array) config('nara.schema_urls') ?: self::SCHEMA_URLS;
    }
}
