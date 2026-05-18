<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class DatabaseBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup the database and remove backups older than 7 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting database backup...');
        Log::info('DatabaseBackup: Starting automated backup process.');

        $filename = 'backup-' . date('Y-m-d-H-i-s') . '.sql';
        $path = storage_path('app/backups');

        // Create directory if it doesn't exist
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }

        $filepath = $path . DIRECTORY_SEPARATOR . $filename;

        // Retrieve DB credentials
        $dbHost = config('database.connections.mysql.host');
        $dbPort = config('database.connections.mysql.port', 3306);
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');
        $dbName = config('database.connections.mysql.database');

        // Set up pure PHP mysqldump (no proc_open required)
        try {
            $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName}";
            $dumpSettings = [
                'compress' => \Ifsnop\Mysqldump\Mysqldump::NONE,
                'add-drop-table' => true,
            ];

            $dumper = new \Ifsnop\Mysqldump\Mysqldump($dsn, $dbUser, $dbPass, $dumpSettings);
            $dumper->start($filepath);

            $this->info('Backup successfully created at ' . $filepath);
            Log::info('DatabaseBackup: Successfully created backup at ' . $filepath);

            // Cleanup old backups
            $this->cleanupOldBackups($path);

            return Command::SUCCESS;
        } catch (\Exception $exception) {
            $this->error('The backup process failed: ' . $exception->getMessage());
            Log::error('DatabaseBackup: Failed. Error: ' . $exception->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Delete backups older than 7 days
     */
    private function cleanupOldBackups($path)
    {
        $this->info('Cleaning up backups older than 7 days...');
        $files = File::files($path);

        $deletedCount = 0;
        $now = now();

        foreach ($files as $file) {
            if ($file->getExtension() === 'sql') {
                $lastModified = \Carbon\Carbon::createFromTimestamp($file->getMTime());
                if ($lastModified->diffInDays($now) >= 7) {
                    File::delete($file->getPathname());
                    $deletedCount++;
                    Log::info('DatabaseBackup: Deleted old backup ' . $file->getFilename());
                }
            }
        }

        $this->info("Cleanup complete. Removed {$deletedCount} old backup files.");
    }
}
