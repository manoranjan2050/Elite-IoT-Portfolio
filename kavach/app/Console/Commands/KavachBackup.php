<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class KavachBackup extends Command
{
    protected $signature = 'kavach:backup
        {--files : Include signing keys and release zips (full backup)}
        {--keep=14 : Delete backups older than this many days (0 = keep all)}';

    protected $description = 'Create a backup zip (database, optionally keys + releases)';

    public function handle(BackupService $backups): int
    {
        $name = $backups->create((bool) $this->option('files'));
        $this->info("Backup created: {$name}");

        $keep = (int) $this->option('keep');
        if ($keep > 0) {
            $cutoff = now()->subDays($keep)->getTimestamp();
            foreach ($backups->list() as $backup) {
                if ($backup['created_at'] < $cutoff) {
                    @unlink($backups->path($backup['name']));
                    $this->line("Pruned old backup: {$backup['name']}");
                }
            }
        }

        return self::SUCCESS;
    }
}
