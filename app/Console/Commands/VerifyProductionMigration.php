<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDO;

class VerifyProductionMigration extends Command
{
    protected $signature = 'db:verify-production-migration
        {--source= : SQLite database file path. Defaults to current sqlite DB_DATABASE}
        {--target= : Target Laravel connection. Defaults to current connection}
        {--table=* : Verify only the named table. Can be repeated}
        {--include-transient : Include cache, sessions, jobs, and failed_jobs tables}';

    protected $description = 'Compare SQLite source row counts with the target production database after migration.';

    private array $transientTables = [
        'cache',
        'cache_locks',
        'failed_jobs',
        'job_batches',
        'jobs',
        'migrations',
        'password_reset_tokens',
        'sessions',
    ];

    public function handle(): int
    {
        $sourcePath = $this->sourcePath();
        $target = (string) ($this->option('target') ?: Config::get('database.default'));

        if (! is_file($sourcePath)) {
            $this->error("SQLite source file was not found: {$sourcePath}");
            return self::FAILURE;
        }

        $source = new PDO('sqlite:' . $sourcePath);
        $source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $tables = $this->selectedTables($source);
        $rows = [];
        $failed = false;

        foreach ($tables as $table) {
            $sourceCount = (int) $source->query('select count(*) from "' . str_replace('"', '""', $table) . '"')->fetchColumn();
            $targetExists = Schema::connection($target)->hasTable($table);
            $targetCount = $targetExists ? (int) DB::connection($target)->table($table)->count() : null;
            $status = $targetExists && $sourceCount === $targetCount ? 'OK' : 'MISMATCH';

            if ($status !== 'OK') {
                $failed = true;
            }

            $rows[] = [$table, $sourceCount, $targetExists ? $targetCount : 'missing', $status];
        }

        $this->table(['Table', 'SQLite rows', 'Target rows', 'Status'], $rows);

        $this->line('');
        $this->line('Manual workflow checks still required: login, admin dashboard, officer dashboard, school search, subject filtering, candidate loading, existing marks, autosave, editing marks, completion notification, audit activity, bulk import, and reports/PDF downloads.');

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function sourcePath(): string
    {
        $source = $this->option('source');
        if ($source) {
            return base_path($source);
        }

        $configured = env('SQLITE_MIGRATION_SOURCE')
            ?: Config::get('database.connections.sqlite.database', database_path('database.sqlite'));
        return $this->isAbsolutePath((string) $configured)
            ? (string) $configured
            : base_path((string) $configured);
    }

    private function selectedTables(PDO $source): array
    {
        $tables = $source
            ->query("select name from sqlite_master where type='table' and name not like 'sqlite_%' order by name")
            ->fetchAll(PDO::FETCH_COLUMN);

        $requested = array_filter((array) $this->option('table'));
        if (! empty($requested)) {
            $tables = array_values(array_intersect($tables, $requested));
        }

        if (! $this->option('include-transient')) {
            $tables = array_values(array_diff($tables, $this->transientTables));
        }

        return $tables;
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR)
            || (bool) preg_match('/^[A-Za-z]:[\\\\\\/]/', $path);
    }
}
