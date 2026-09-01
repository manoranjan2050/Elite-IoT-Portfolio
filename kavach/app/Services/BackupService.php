<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use ZipArchive;

/**
 * Backup & restore without shell commands — pure PHP, so it works on any
 * shared hosting (no mysqldump binary needed).
 *
 * A backup zip contains:
 *   database.sql   — full dump (MySQL) with a custom statement delimiter
 *   database.sqlite — the raw file instead, when running on SQLite (dev)
 *   keys/…         — Ed25519 signing keys      (full backup only)
 *   releases/…     — uploaded release zips     (full backup only)
 */
class BackupService
{
    /** Unambiguous statement separator so restore never mis-splits on data. */
    private const DELIMITER = "\n-- KAVACH_STMT --\n";

    public function backupDir(): string
    {
        $dir = storage_path('app/backups');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        return $dir;
    }

    /** @return array<int, array{name: string, size: int, created_at: int}> */
    public function list(): array
    {
        $backups = [];
        foreach (glob($this->backupDir().'/*.zip') as $file) {
            $backups[] = [
                'name' => basename($file),
                'size' => (int) filesize($file),
                'created_at' => (int) filemtime($file),
            ];
        }

        usort($backups, fn ($a, $b) => $b['created_at'] <=> $a['created_at']);

        return $backups;
    }

    /** Validate a backup name and return its absolute path. */
    public function path(string $name): string
    {
        $name = basename($name);
        if (! preg_match('/^[\w\-\.]+\.zip$/', $name)) {
            throw new RuntimeException('Invalid backup file name.');
        }

        $path = $this->backupDir().DIRECTORY_SEPARATOR.$name;
        if (! is_file($path)) {
            throw new RuntimeException('Backup file not found.');
        }

        return $path;
    }

    /** Create a backup; returns the created file name. */
    public function create(bool $includeFiles = false): string
    {
        $name = 'kavach-backup-'.now()->format('Y-m-d-His').($includeFiles ? '-full' : '-db').'.zip';
        $zipPath = $this->backupDir().DIRECTORY_SEPARATOR.$name;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create backup zip.');
        }

        if (DB::connection()->getDriverName() === 'sqlite') {
            $zip->addFile(DB::connection()->getDatabaseName(), 'database.sqlite');
        } else {
            $zip->addFromString('database.sql', $this->dumpMysql());
        }

        if ($includeFiles) {
            $this->addFolder($zip, storage_path('app/private/keys'), 'keys');
            $this->addFolder($zip, storage_path('app/private/releases'), 'releases');
        }

        $zip->addFromString('kavach-backup.json', json_encode([
            'app' => 'kavach',
            'created_at' => now()->toIso8601String(),
            'driver' => DB::connection()->getDriverName(),
            'full' => $includeFiles,
        ]));

        if (! $zip->close()) {
            throw new RuntimeException('Could not finish backup zip.');
        }

        return $name;
    }

    /** Restore database (and keys/releases when present) from a backup zip. */
    public function restore(string $name): void
    {
        $path = $this->path($name);

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Could not open backup zip.');
        }

        if ($zip->locateName('kavach-backup.json') === false) {
            $zip->close();
            throw new RuntimeException('Not a Kavach backup file (missing kavach-backup.json).');
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            if ($zip->locateName('database.sqlite') === false) {
                $zip->close();
                throw new RuntimeException('This backup has a MySQL dump; the current app uses SQLite.');
            }
            copy("zip://{$path}#database.sqlite", DB::connection()->getDatabaseName());
        } else {
            $sql = $zip->getFromName('database.sql');
            if ($sql === false) {
                $zip->close();
                throw new RuntimeException('This backup has an SQLite file; the current app uses MySQL.');
            }
            $this->importMysql($sql);
        }

        // Restore signing keys and release files if the backup contains them.
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            foreach (['keys/', 'releases/'] as $prefix) {
                if (str_starts_with($entry, $prefix) && ! str_ends_with($entry, '/') && ! str_contains($entry, '..')) {
                    $target = storage_path('app/private/'.$entry);
                    if (! is_dir(dirname($target))) {
                        mkdir(dirname($target), 0775, true);
                    }
                    copy("zip://{$path}#{$entry}", $target);
                }
            }
        }

        $zip->close();
    }

    /* ------------------------------------------------------------------ */

    private function dumpMysql(): string
    {
        $pdo = DB::getPdo();
        $sql = "-- Kavach backup ".now()->toIso8601String()."\nSET FOREIGN_KEY_CHECKS=0;".self::DELIMITER;

        $tables = array_map(
            fn ($row) => array_values((array) $row)[0],
            DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'"),
        );

        foreach ($tables as $table) {
            $create = (array) DB::selectOne("SHOW CREATE TABLE `{$table}`");
            $createSql = array_values($create)[1];

            $sql .= "DROP TABLE IF EXISTS `{$table}`;".self::DELIMITER;
            $sql .= $createSql.';'.self::DELIMITER;

            $stmt = $pdo->query("SELECT * FROM `{$table}`");
            $rows = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $values = array_map(
                    fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v),
                    array_values($row),
                );
                $rows[] = '('.implode(',', $values).')';

                if (count($rows) === 200) {
                    $sql .= "INSERT INTO `{$table}` VALUES ".implode(',', $rows).';'.self::DELIMITER;
                    $rows = [];
                }
            }
            if ($rows) {
                $sql .= "INSERT INTO `{$table}` VALUES ".implode(',', $rows).';'.self::DELIMITER;
            }
        }

        return $sql."SET FOREIGN_KEY_CHECKS=1;".self::DELIMITER;
    }

    private function importMysql(string $sql): void
    {
        $pdo = DB::getPdo();

        foreach (explode(self::DELIMITER, $sql) as $statement) {
            $statement = trim($statement);
            if ($statement !== '') {
                $pdo->exec($statement);
            }
        }
    }

    private function addFolder(ZipArchive $zip, string $dir, string $zipPrefix): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (glob($dir.'/*') as $file) {
            if (is_file($file)) {
                $zip->addFile($file, $zipPrefix.'/'.basename($file));
            }
        }
    }
}
