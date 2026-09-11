<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use PDO;

/**
 * Copy every row from the local SQLite database into the MySQL connection
 * defined in config/database.php (the default one in .env).
 *
 * Use case: the project's .env was authored with MySQL credentials, but the
 * dev environment has been running against SQLite (database/database.sqlite)
 * for weeks. The MySQL `pod` database has tables but no real data. This
 * command transfers the SQLite snapshot into MySQL so the web server,
 * which now boots against MySQL, sees the same data the dev has been
 * working with.
 *
 * Tables are walked in FK-safe parent-first order; the destination tables
 * are truncated before each insert to avoid duplicate-key collisions.
 *
 * --dry-run previews the row counts that would be copied, no writes.
 */
#[Signature('migrate:sqlite-to-mysql
    {--sqlite= : Path to the SQLite file (defaults to database/database.sqlite)}
    {--dry-run : Report what would be copied without touching MySQL}')]
#[Description('Copy every row from the local SQLite database into the default MySQL connection.')]
class MigrateDataSqliteToMysql extends Command
{
    /**
     * Topological-ish order: parents before children. Self-FKs (categories)
     * are seeded by the DB itself with NULL so they pass on the first pass.
     */
    private const TABLE_ORDER = [
        'users',
        'designer_profiles',
        'printer_provider_profiles',
        'categories',
        'tags',
        'product_templates',
        'product_variants',
        'designs',
        'design_product_mappings',
        'design_tag',
        'addresses',
        'delivery_companies',
        'orders',
        'order_items',
        'payments',
        'shipments',
        'media',
        'cart_items',
        'settings',
        'notifications',
        'personal_access_tokens',
        'password_reset_tokens',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'sessions',
    ];

    public function handle(): int
    {
        $sqlitePath = (string) ($this->option('sqlite') ?: database_path('database.sqlite'));

        if (! is_file($sqlitePath)) {
            $this->error("SQLite file not found: {$sqlitePath}");

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        $sqlite = new PDO("sqlite:{$sqlitePath}", null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        $dest = DB::connection();
        $this->info(sprintf(
            'Source: SQLite %s',
            $sqlitePath,
        ));
        $this->info(sprintf(
            'Destination: %s @ %s/%s',
            $dest->getDriverName(),
            $dest->getConfig('host') ?? 'localhost',
            $dest->getDatabaseName(),
        ));
        $this->newLine();

        // Only consider tables that actually exist in both DBs.
        $sqliteTables = $this->listTables($sqlite);
        $destTables = $this->listTables($dest->getPdo(), $dest->getDriverName());

        $tables = array_values(array_intersect(self::TABLE_ORDER, $sqliteTables, $destTables));

        if ($dryRun) {
            return $this->reportDryRun($sqlite, $tables);
        }

        $copied = 0;

        // Disable FK checks on MySQL for the duration of the transfer so we
        // can wipe tables out of FK-safe order without 1451 errors.
        if ($dest->getDriverName() === 'mysql') {
            $dest->statement('SET FOREIGN_KEY_CHECKS=0');
        }

        // MySQL STORED GENERATED columns (e.g. cart_items.variant_key) are
        // auto-computed from other columns and reject explicit writes.
        // Build a per-table set of columns to skip on insert.
        $generatedColumns = $this->generatedColumns($dest, $dest->getDriverName());

        foreach ($tables as $table) {
            $rows = $sqlite->query("SELECT * FROM \"{$table}\"")->fetchAll(PDO::FETCH_ASSOC);
            $rowCount = count($rows);
            $skipCols = $generatedColumns[$table] ?? [];

            // Wipe destination so we don't collide with existing rows.
            $dest->table($table)->delete();
            // Reset autoincrement so future inserts get fresh ids.
            $dest->statement("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");

            foreach ($rows as $row) {
                $row = $this->normalizeRow($row);
                foreach ($skipCols as $col) {
                    unset($row[$col]);
                }
                $dest->table($table)->insert($row);
            }

            $this->line(sprintf('  %-30s %d rows', $table, $rowCount));
            $copied += $rowCount;
        }

        if ($dest->getDriverName() === 'mysql') {
            $dest->statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $this->newLine();
        $this->info("Copied {$copied} rows across ".count($tables).' tables.');

        return self::SUCCESS;
    }

    /**
     * Make sure values land in MySQL the way MySQL expects:
     * - JSON columns: SQLite stores JSON-as-TEXT; pass through unchanged and
     *   let MySQL cast on insert. Strip the surrounding quotes if present.
     * - DATETIME: SQLite stores 'Y-m-d H:i:s'; pass through unchanged.
     * - INTEGER 0/1 for booleans: same on both sides.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeRow(array $row): array
    {
        foreach ($row as $key => $value) {
            if (is_string($value) && $value === '') {
                // SQLite may return '' for NULL TEXT — keep as NULL so MySQL
                // nullable columns accept it.
                $row[$key] = null;
            }
            if (is_string($value) && $this->looksLikeJson($value)) {
                // Strip SQLite's wrapping quotes if any (it stores JSON as
                // raw text without wrapping). No-op for already-clean JSON.
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $row[$key] = json_encode($decoded);
                }
            }
        }

        return $row;
    }

    private function looksLikeJson(string $value): bool
    {
        $trim = ltrim($value);

        return $trim !== '' && ($trim[0] === '{' || $trim[0] === '[');
    }

    /**
     * @return array<int, string>
     */
    private function listTables(PDO $pdo, ?string $driver = null): array
    {
        if ($driver === 'mysql') {
            $rows = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM);

            return array_map(fn ($r) => $r[0], $rows);
        }
        $rows = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN);

        return array_map('strval', $rows);
    }

    /**
     * Return a [table => [generatedColumn, ...]] map for the given driver.
     * MySQL STORED GENERATED columns are auto-computed; SQLite has no such
     * concept, so transferring them verbatim fails on MySQL.
     *
     * @return array<string, array<int, string>>
     */
    private function generatedColumns(Connection $connection, string $driver): array
    {
        if ($driver !== 'mysql') {
            return [];
        }

        $rows = $connection->select(
            "SELECT TABLE_NAME, COLUMN_NAME
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND EXTRA LIKE '%GENERATED%'"
        );

        $map = [];
        foreach ($rows as $r) {
            $map[$r->TABLE_NAME][] = $r->COLUMN_NAME;
        }

        return $map;
    }

    /**
     * @param  array<int, string>  $tables
     */
    private function reportDryRun(PDO $sqlite, array $tables): int
    {
        $this->warn('DRY RUN — no writes will be made.');
        $this->newLine();
        $this->table(['table', 'source rows'], array_map(
            fn (string $t): array => [$t, (int) $sqlite->query("SELECT COUNT(*) FROM \"{$t}\"")->fetchColumn()],
            $tables,
        ));

        return self::SUCCESS;
    }
}
