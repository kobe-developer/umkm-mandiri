<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

#[Signature('app:backup-database {--keep=14 : Jumlah file backup yang disimpan}')]
#[Description('Backup database harian dengan mysqldump (gzip) ke storage/app/backups')]
class BackupDatabase extends Command
{
    public function handle(): int
    {
        $config = config('database.connections.'.config('database.default'));
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);
        $file = $dir.'/'.$config['database'].'_'.now()->format('Ymd_His').'.sql.gz';

        $process = Process::fromShellCommandline(
            'mysqldump --single-transaction --skip-lock-tables --routines -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$DB_NAME" | gzip > "$OUT_FILE"',
            null,
            [
                'DB_HOST' => $config['host'],
                'DB_PORT' => (string) $config['port'],
                'DB_USER' => $config['username'],
                'DB_NAME' => $config['database'],
                'MYSQL_PWD' => $config['password'],
                'OUT_FILE' => $file,
            ],
            null,
            600,
        );
        $process->run();

        if (! $process->isSuccessful() || filesize($file) === 0) {
            File::delete($file);
            $this->error('Backup gagal: '.$process->getErrorOutput());

            return self::FAILURE;
        }

        $this->prune($dir, (int) $this->option('keep'));
        $this->info("Backup tersimpan: {$file}");

        return self::SUCCESS;
    }

    private function prune(string $dir, int $keep): void
    {
        collect(File::files($dir))
            ->sortByDesc(fn ($f) => $f->getMTime())
            ->slice($keep)
            ->each(fn ($f) => File::delete($f->getPathname()));
    }
}
