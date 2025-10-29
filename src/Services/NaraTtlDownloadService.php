<?php

namespace KraenzleRitter\NaraRisk\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * NARA TTL File Download and Caching Service
 */
class NaraTtlDownloadService
{
    private const TTL_FILES = [
        'fileformats' => 'https://www.archives.gov/files/lod/dpframework/fileformats.ttl',
        'presaction' => 'https://www.archives.gov/files/lod/dpframework/presaction.ttl',
        'category' => 'https://www.archives.gov/files/lod/dpframework/category.ttl',
        'risk' => 'https://www.archives.gov/files/lod/dpframework/risk.ttl',
        'schema' => 'https://www.archives.gov/files/lod/dpframework/dpframeworkschema.ttl',
    ];

    private const CACHE_FILENAME = 'nara_fileformats.ttl';
    private const CACHE_DAYS = 28; // 4 weeks

    private string $cachePath;

    public function __construct()
    {
        // Store in storage/app/nara/ directory
        $this->cachePath = storage_path('app/nara');
        if (! is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0o755, true);
        }
    }

    /**
     * Get TTL content - download if needed or use cache
     */
    public function getTtlContent(): string
    {
        $cacheFile = $this->cachePath . '/' . self::CACHE_FILENAME;

        // Check if cache exists and is recent enough
        if (file_exists($cacheFile) && $this->isCacheValid($cacheFile)) {
            Log::info('Using cached NARA TTL file', ['file' => $cacheFile]);

            return file_get_contents($cacheFile);
        }

        // Download fresh TTL data
        return $this->downloadAndCache();
    }

    /**
     * Download all NARA schema files
     */
    public function downloadAllSchemaFiles(): array
    {
        $results = [];

        foreach (self::TTL_FILES as $name => $url) {
            try {
                $filename = "nara_{$name}.ttl";
                $content = $this->downloadFile($url, $filename);
                $results[$name] = [
                    'success' => true,
                    'filename' => $filename,
                    'size' => strlen($content),
                ];
                Log::info("Downloaded NARA {$name} file", ['filename' => $filename]);
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
    private function downloadFile(string $url, string $filename): string
    {
        $cacheFile = $this->cachePath . '/' . $filename;

        // Check if file exists and is recent
        if (file_exists($cacheFile) && $this->isCacheValid($cacheFile)) {
            return file_get_contents($cacheFile);
        }

        Log::info('Downloading NARA TTL file', ['url' => $url]);

        $context = stream_context_create([
            'http' => [
                'timeout' => 60,
                'user_agent' => 'AgateSipLine/1.0 (Digital Preservation Tool)',
                'follow_location' => true,
                'max_redirects' => 5,
            ],
        ]);

        $ttlContent = file_get_contents($url, false, $context);

        if ($ttlContent === false) {
            throw new \Exception('Failed to download TTL file from ' . $url);
        }

        // Validate TTL content
        if (! str_contains($ttlContent, '@prefix')) {
            throw new \Exception('Downloaded content does not appear to be valid TTL');
        }

        // Save to cache
        if (file_put_contents($cacheFile, $ttlContent) === false) {
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
        $expiryTime = Carbon::now()->subDays(self::CACHE_DAYS)->timestamp;

        return $fileTime > $expiryTime;
    }

    /**
     * Download TTL file and save to cache
     */
    private function downloadAndCache(): string
    {
        $cacheFile = $this->cachePath . '/' . self::CACHE_FILENAME;
        $url = self::TTL_FILES['fileformats'];

        try {
            $ttlContent = $this->downloadFile($url, self::CACHE_FILENAME);

            return $ttlContent;

        } catch (\Exception $e) {
            Log::error('Failed to download NARA TTL file', [
                'error' => $e->getMessage(),
                'url' => $url,
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
        $cacheFile = $this->cachePath . '/' . self::CACHE_FILENAME;

        if (! file_exists($cacheFile)) {
            return [
                'exists' => false,
                'path' => $cacheFile,
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
            'expires' => Carbon::createFromTimestamp($fileTime)->addDays(self::CACHE_DAYS)->toISOString(),
            'age_days' => Carbon::now()->diffInDays(Carbon::createFromTimestamp($fileTime)),
        ];
    }

    /**
     * Force refresh of TTL cache
     */
    public function refreshCache(): string
    {
        $cacheFile = $this->cachePath . '/' . self::CACHE_FILENAME;

        // Remove old cache
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }

        return $this->downloadAndCache();
    }
}
