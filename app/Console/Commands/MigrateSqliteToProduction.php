<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDO;
use Throwable;

class MigrateSqliteToProduction extends Command
{
    protected $signature = 'db:migrate-sqlite-to-production
        {--source= : SQLite database file path. Defaults to current sqlite DB_DATABASE}
        {--target= : Target Laravel connection. Defaults to current non-sqlite default connection}
        {--table=* : Migrate only the named table. Can be repeated}
        {--dry-run : Report planned work without inserting rows}
        {--replace-target-data : Delete selected target table rows before copying. Use only on a freshly migrated target database}
        {--include-transient : Include cache, sessions, jobs, and failed_jobs tables}
        {--chunk=500 : Number of source rows to read and insert per batch}';

    protected $description = 'Safely copy data from the SQLite database into a migrated MySQL/MariaDB database.';

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
        $target = $this->targetConnection();
        $dryRun = (bool) $this->option('dry-run');
        $replaceTargetData = (bool) $this->option('replace-target-data');
        $chunkSize = $this->chunkSize();

        if ($chunkSize === null) {
            return self::FAILURE;
        }

        if (! is_file($sourcePath)) {
            $this->error("SQLite source file was not found: {$sourcePath}");
            return self::FAILURE;
        }

        $targetDriver = DB::connection($target)->getDriverName();
        if (! in_array($targetDriver, ['mysql', 'mariadb'], true)) {
            $this->error('Target connection must be MySQL/MariaDB.');
            return self::FAILURE;
        }

        $source = new PDO('sqlite:' . $sourcePath);
        $source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $tables = $this->selectedTables($source);
        if (empty($tables)) {
            $this->warn('No source tables selected.');
            return self::SUCCESS;
        }

        $this->info(($dryRun ? 'Dry run: ' : '') . "SQLite source: {$sourcePath}");
        $this->info("Target connection: {$target} (" . DB::connection($target)->getDriverName() . ')');
        if ($replaceTargetData) {
            $this->warn('Target replacement mode enabled. Selected target tables will be cleared before insert when not in dry-run mode.');
        }

        $errors = 0;
        $insertedTotal = 0;

        try {
            Schema::connection($target)->disableForeignKeyConstraints();

            if ($replaceTargetData && ! $dryRun) {
                $this->clearTargetTables($target, $tables);
            }

            foreach ($tables as $table) {
                $result = $this->migrateTable($source, $target, $table, $dryRun, $replaceTargetData, $chunkSize);
                $insertedTotal += $result['inserted'];
                $errors += $result['errors'];

                if ($errors > 0) {
                    break;
                }
            }
        } catch (Throwable $exception) {
            $errors++;
            $this->error($exception->getMessage());
        } finally {
            Schema::connection($target)->enableForeignKeyConstraints();
        }

        if ($errors > 0) {
            $this->error('Migration stopped safely. Source SQLite data was not modified.');
            return self::FAILURE;
        }

        $this->info("Completed. Inserted rows: {$insertedTotal}");
        return self::SUCCESS;
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

    private function targetConnection(): string
    {
        return (string) ($this->option('target') ?: Config::get('database.default'));
    }

    private function chunkSize(): ?int
    {
        $chunk = $this->option('chunk');

        if (! is_numeric($chunk) || (string) (int) $chunk !== (string) $chunk || (int) $chunk < 1) {
            $this->error('The --chunk option must be a positive integer.');
            return null;
        }

        return (int) $chunk;
    }

    private function selectedTables(PDO $source): array
    {
        $sourceTables = $source
            ->query("select name from sqlite_master where type='table' and name not like 'sqlite_%' order by name")
            ->fetchAll(PDO::FETCH_COLUMN);

        $requested = array_filter((array) $this->option('table'));
        if (! empty($requested)) {
            $sourceTables = array_values(array_intersect($sourceTables, $requested));
        }

        if (! $this->option('include-transient')) {
            $sourceTables = array_values(array_diff($sourceTables, $this->transientTables));
        }

        $ordered = [];
        foreach ($this->migrationTableOrder() as $table) {
            if (in_array($table, $sourceTables, true) && ! in_array($table, $ordered, true)) {
                $ordered[] = $table;
            }
        }

        foreach ($sourceTables as $table) {
            if (! in_array($table, $ordered, true)) {
                $ordered[] = $table;
            }
        }

        return $ordered;
    }

    private function migrationTableOrder(): array
    {
        $tables = [];
        foreach (glob(database_path('migrations/*.php')) ?: [] as $file) {
            $code = (string) file_get_contents($file);
            if (preg_match_all("/Schema::create\\(['\"]([^'\"]+)['\"]/", $code, $matches)) {
                foreach ($matches[1] as $table) {
                    $tables[] = $table;
                }
            }
        }

        return $tables;
    }

    private function migrateTable(PDO $source, string $target, string $table, bool $dryRun, bool $replaceTargetData, int $chunkSize): array
    {
        if (! Schema::connection($target)->hasTable($table)) {
            $this->error("Target table is missing: {$table}");
            return ['inserted' => 0, 'errors' => 1];
        }

        $sourceColumns = $this->sourceColumns($source, $table);
        $sourceColumnTypes = $this->sourceColumnTypes($source, $table);
        $targetColumns = Schema::connection($target)->getColumnListing($table);
        $missingColumns = array_values(array_diff($sourceColumns, $targetColumns));

        if (! empty($missingColumns)) {
            $this->error("Target table {$table} is missing source columns: " . implode(', ', $missingColumns));
            return ['inserted' => 0, 'errors' => 1];
        }

        $sourceCount = (int) $source->query('select count(*) from "' . str_replace('"', '""', $table) . '"')->fetchColumn();
        $targetCount = (int) DB::connection($target)->table($table)->count();

        $duplicateIds = $this->duplicateSourceIds($source, $table, $sourceColumns);
        if ($duplicateIds > 0) {
            $this->error("Source table {$table} has duplicate id values.");
            return ['inserted' => 0, 'errors' => 1];
        }

        $existingIds = [];
        if ($targetCount > 0 && $sourceCount > 0 && ! $replaceTargetData) {
            if (! in_array('id', $sourceColumns, true) || ! in_array('id', $targetColumns, true)) {
                $this->error("Target table {$table} is not empty ({$targetCount} rows) and cannot be safely merged without id columns.");
                return ['inserted' => 0, 'errors' => 1];
            }

            $mergeCheck = $this->checkExistingRowsMatch($source, $target, $table, $sourceColumns);
            if ($mergeCheck['conflicts'] > 0) {
                $this->error("Target table {$table} has {$mergeCheck['conflicts']} conflicting existing row(s). Refusing to merge.");
                return ['inserted' => 0, 'errors' => 1];
            }

            $existingIds = $mergeCheck['existing_ids'];
        }

        $insertCount = $replaceTargetData
            ? $sourceCount
            : (in_array('id', $sourceColumns, true) ? $sourceCount - count($existingIds) : $sourceCount);
        if ($insertCount < 0) {
            $this->error("Target table {$table} contains more matching ids than source rows. Refusing to merge.");
            return ['inserted' => 0, 'errors' => 1];
        }

        if ($dryRun || $sourceCount === 0) {
            $this->line("{$table}: source={$sourceCount}, target={$targetCount}, inserted=" . ($dryRun ? $insertCount : 0));
            return ['inserted' => 0, 'errors' => 0];
        }

        $quotedColumns = implode(', ', array_map(fn ($column) => '"' . str_replace('"', '""', $column) . '"', $sourceColumns));
        $order = in_array('id', $sourceColumns, true) ? ' order by "id"' : '';
        $stmt = $source->prepare('select ' . $quotedColumns . ' from "' . str_replace('"', '""', $table) . '"' . $order . ' limit :limit offset :offset');

        $inserted = 0;
        $normalizedTextValues = 0;
        $existingLookup = array_fill_keys($existingIds, true);
        for ($offset = 0; $offset < $sourceCount; $offset += $chunkSize) {
            $stmt->bindValue(':limit', $chunkSize, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (! empty($existingLookup)) {
                $rows = array_values(array_filter($rows, fn (array $row) => ! isset($existingLookup[(string) $row['id']])));
            }

            if (! empty($rows)) {
                $normalization = $this->normalizeRowsForTarget($rows, $sourceColumnTypes);
                $rows = $normalization['rows'];
                $normalizedTextValues += $normalization['normalized_text_values'];

                $chunkNumber = (int) floor($offset / $chunkSize) + 1;

                try {
                    DB::connection($target)->table($table)->insert($rows);
                } catch (Throwable $exception) {
                    $rangeStart = $offset + 1;
                    $rangeEnd = min($offset + $chunkSize, $sourceCount);

                    $this->error("Failed inserting {$table} chunk {$chunkNumber} (approx source rows {$rangeStart}-{$rangeEnd}): {$this->conciseExceptionMessage($exception)}");

                    return ['inserted' => $inserted, 'errors' => 1];
                }

                $inserted += count($rows);
                $this->line("{$table}: inserted {$inserted} / {$insertCount}");
            }
        }

        $this->line("{$table}: source={$sourceCount}, target={$targetCount}, inserted={$inserted}");
        if ($normalizedTextValues > 0) {
            $this->warn("{$table}: normalized {$normalizedTextValues} invalid UTF-8 text value(s) while copying to target. Source SQLite data was not modified.");
        }

        return ['inserted' => $inserted, 'errors' => 0];
    }

    private function clearTargetTables(string $target, array $tables): void
    {
        foreach (array_reverse($tables) as $table) {
            if (Schema::connection($target)->hasTable($table)) {
                DB::connection($target)->table($table)->delete();
            }
        }
    }

    private function sourceColumns(PDO $source, string $table): array
    {
        $columns = $source
            ->query('PRAGMA table_info("' . str_replace('"', '""', $table) . '")')
            ->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn (array $column) => $column['name'], $columns);
    }

    private function sourceColumnTypes(PDO $source, string $table): array
    {
        $columns = $source
            ->query('PRAGMA table_info("' . str_replace('"', '""', $table) . '")')
            ->fetchAll(PDO::FETCH_ASSOC);

        $types = [];
        foreach ($columns as $column) {
            $types[$column['name']] = strtolower((string) $column['type']);
        }

        return $types;
    }

    private function normalizeRowsForTarget(array $rows, array $columnTypes): array
    {
        $normalizedTextValues = 0;

        foreach ($rows as &$row) {
            foreach ($row as $column => $value) {
                if (! is_string($value) || $this->isBinaryColumn($columnTypes[$column] ?? '')) {
                    continue;
                }

                if (! mb_check_encoding($value, 'UTF-8')) {
                    $row[$column] = mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
                    $normalizedTextValues++;
                }
            }
        }
        unset($row);

        return [
            'rows' => $rows,
            'normalized_text_values' => $normalizedTextValues,
        ];
    }

    private function isBinaryColumn(string $type): bool
    {
        return str_contains($type, 'blob') || str_contains($type, 'binary');
    }

    private function duplicateSourceIds(PDO $source, string $table, ?array $columns = null): int
    {
        if (! in_array('id', $columns ?: $this->sourceColumns($source, $table), true)) {
            return 0;
        }

        return (int) $source
            ->query('select count(*) from (select id from "' . str_replace('"', '""', $table) . '" group by id having count(*) > 1)')
            ->fetchColumn();
    }

    private function checkExistingRowsMatch(PDO $source, string $target, string $table, array $sourceColumns): array
    {
        $targetRows = DB::connection($target)
            ->table($table)
            ->select($sourceColumns)
            ->whereNotNull('id')
            ->get()
            ->mapWithKeys(fn (object $row) => [(string) $row->id => (array) $row])
            ->all();

        if (empty($targetRows)) {
            return ['conflicts' => 0, 'existing_ids' => []];
        }

        $quotedColumns = implode(', ', array_map(fn ($column) => '"' . str_replace('"', '""', $column) . '"', $sourceColumns));
        $sourceRows = $source
            ->query('select ' . $quotedColumns . ' from "' . str_replace('"', '""', $table) . '"')
            ->fetchAll(PDO::FETCH_ASSOC);

        $sourceById = [];
        foreach ($sourceRows as $row) {
            $sourceById[(string) $row['id']] = $row;
        }

        $conflicts = 0;
        $existingIds = [];
        foreach ($targetRows as $id => $targetRow) {
            if (! isset($sourceById[$id])) {
                continue;
            }

            if (! $this->rowsMatch($sourceById[$id], $targetRow, $sourceColumns)) {
                $conflicts++;
            }

            $existingIds[] = $id;
        }

        return ['conflicts' => $conflicts, 'existing_ids' => $existingIds];
    }

    private function rowsMatch(array $sourceRow, array $targetRow, array $columns): bool
    {
        foreach ($columns as $column) {
            if ($this->normalizeValue($sourceRow[$column] ?? null) !== $this->normalizeValue($targetRow[$column] ?? null)) {
                return false;
            }
        }

        return true;
    }

    private function normalizeValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }

    private function conciseExceptionMessage(Throwable $exception): string
    {
        return $exception->getPrevious()?->getMessage() ?: $exception->getMessage();
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR)
            || (bool) preg_match('/^[A-Za-z]:[\\\\\\/]/', $path);
    }
}
