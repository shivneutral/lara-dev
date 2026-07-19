<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportCustomers extends Command
{
    protected $signature = 'customers:import
        {path=storage/app/customers.csv : Path to the CSV file}
        {--chunk=2000 : Number of rows to upsert per batch}';

    protected $description = 'Stream-import a large customers CSV in batches without exhausting memory or aborting on bad rows';

    private const REQUIRED_COLUMNS = ['name', 'email'];
    private const ALLOWED_COLUMNS = ['name', 'email', 'phone', 'address'];

    public function handle(): int
    {
        $path = $this->resolvePath($this->argument('path'));
        $chunkSize = max(1, (int) $this->option('chunk'));

        if (! is_file($path) || ! is_readable($path)) {
            $this->error("File not found or not readable: {$path}");

            return self::FAILURE;
        }

        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);

        if ($header === false) {
            $this->error('CSV file is empty.');
            fclose($handle);

            return self::FAILURE;
        }

        $header = array_map(fn ($col) => strtolower(trim($col)), $header);
        $missing = array_diff(self::REQUIRED_COLUMNS, $header);

        if ($missing !== []) {
            $this->error('CSV is missing required column(s): '.implode(', ', $missing));
            fclose($handle);

            return self::FAILURE;
        }

        $errorPath = dirname($path).'/'.pathinfo($path, PATHINFO_FILENAME).'_errors.csv';
        $errorHandle = fopen($errorPath, 'w');
        fputcsv($errorHandle, [...$header, 'reason'], escape: '\\');

        DB::connection()->disableQueryLog();
        $this->tuneSqliteForBulkImport();

        $totalRows = $this->countDataRows($path);
        $bar = $this->output->createProgressBar($totalRows);
        $bar->start();

        $batch = [];
        $imported = 0;
        $skipped = 0;
        $now = now();

        while (($row = fgetcsv($handle)) !== false) {
            $bar->advance();

            if (count($row) !== count($header)) {
                $this->logRejected($errorHandle, $row, 'Column count mismatch');
                $skipped++;

                continue;
            }

            $record = array_combine($header, $row);
            $record = array_intersect_key($record, array_flip(self::ALLOWED_COLUMNS));

            $name = trim($record['name'] ?? '');
            $email = trim($record['email'] ?? '');

            if ($name === '' || $email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->logRejected($errorHandle, $row, 'Missing name or invalid email');
                $skipped++;

                continue;
            }

            $batch[] = [
                'name' => $name,
                'email' => $email,
                'phone' => trim($record['phone'] ?? '') ?: null,
                'address' => trim($record['address'] ?? '') ?: null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) >= $chunkSize) {
                $imported += $this->flushBatch($batch, $errorHandle);
                $batch = [];
            }
        }

        if ($batch !== []) {
            $imported += $this->flushBatch($batch, $errorHandle);
        }

        $bar->finish();
        fclose($handle);
        fclose($errorHandle);
        $this->restoreSqliteDefaults();

        $this->newLine(2);
        $this->info("Imported/updated: {$imported}");
        $this->info("Skipped: {$skipped}");

        if ($skipped > 0) {
            $this->warn("Rejected rows written to: {$errorPath}");
        } else {
            @unlink($errorPath);
        }

        return self::SUCCESS;
    }

    /**
     * Upsert a batch, falling back to per-row upserts if the batch itself
     * throws (e.g. an unexpected constraint violation), so one bad row
     * can never abort the whole import.
     */
    private function flushBatch(array $batch, $errorHandle): int
    {
        try {
            DB::table('customers')->upsert(
                $batch,
                ['email'],
                ['name', 'phone', 'address', 'updated_at']
            );

            return count($batch);
        } catch (\Throwable $e) {
            $imported = 0;

            foreach ($batch as $row) {
                try {
                    DB::table('customers')->upsert([$row], ['email'], ['name', 'phone', 'address', 'updated_at']);
                    $imported++;
                } catch (\Throwable $rowError) {
                    Log::warning('customers:import row failed', ['row' => $row, 'error' => $rowError->getMessage()]);
                    fputcsv($errorHandle, [...array_values($row), $rowError->getMessage()], escape: '\\');
                }
            }

            return $imported;
        }
    }

    private function logRejected($errorHandle, array $row, string $reason): void
    {
        fputcsv($errorHandle, [...$row, $reason], escape: '\\');
    }

    private function resolvePath(string $path): string
    {
        return str_starts_with($path, '/') ? $path : base_path($path);
    }

    /**
     * Counting rows up front costs one cheap streaming pass but turns the
     * progress bar into a real percentage instead of a guess.
     */
    private function countDataRows(string $path): int
    {
        $handle = fopen($path, 'r');
        $count = -1; // header line doesn't count

        while (! feof($handle)) {
            $count += substr_count(fread($handle, 1024 * 1024), "\n");
        }

        fclose($handle);

        return max($count, 0);
    }

    private function tuneSqliteForBulkImport(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('PRAGMA synchronous = OFF');
            DB::statement('PRAGMA journal_mode = WAL');
            DB::statement('PRAGMA temp_store = MEMORY');
        }
    }

    private function restoreSqliteDefaults(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('PRAGMA synchronous = FULL');
            DB::statement('PRAGMA journal_mode = DELETE');
        }
    }
}
