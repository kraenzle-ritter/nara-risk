<?php

namespace KraenzleRitter\NaraRisk\Commands;

use Illuminate\Console\Command;
use KraenzleRitter\NaraRisk\Services\NaraTtlDownloadService;

class DownloadNaraSchema extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nara:download-schema';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download all NARA Digital Preservation Framework schema files (TTL)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Downloading NARA Digital Preservation Framework schema files...');
        $this->newLine();

        $downloadService = new NaraTtlDownloadService();
        $results = $downloadService->downloadAllSchemaFiles();

        foreach ($results as $name => $result) {
            if ($result['success']) {
                $this->info("✓ {$name}: {$result['filename']} ({$result['size']} bytes)");
            } else {
                $this->error("✗ {$name}: {$result['error']}");
            }
        }

        $successCount = count(array_filter($results, fn ($r) => $r['success']));
        $totalCount = count($results);

        $this->newLine();
        if ($successCount === $totalCount) {
            $this->info("All {$totalCount} schema files downloaded successfully!");

            return Command::SUCCESS;
        } else {
            $this->warn("{$successCount}/{$totalCount} schema files downloaded successfully.");

            return Command::FAILURE;
        }
    }
}
