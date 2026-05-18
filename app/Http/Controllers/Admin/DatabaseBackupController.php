<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class DatabaseBackupController extends Controller
{
    /**
     * Display a listing of backups.
     */
    public function index()
    {
        $path = storage_path('app/backups');
        $backups = [];

        if (File::exists($path)) {
            $files = File::files($path);
            foreach ($files as $file) {
                if ($file->getExtension() === 'sql') {
                    $backups[] = [
                        'name' => $file->getFilename(),
                        'size' => $this->humanFilesize($file->getSize()),
                        'date' => \Carbon\Carbon::createFromTimestamp($file->getMTime())->toDateTimeString(),
                        'timestamp' => $file->getMTime(),
                    ];
                }
            }

            // Sort by latest first
            usort($backups, function ($a, $b) {
                return $b['timestamp'] <=> $a['timestamp'];
            });
        }

        return Inertia::render('Admin/Settings/DatabaseBackups', [
            'backups' => $backups,
        ]);
    }

    /**
     * Manually trigger a database backup.
     */
    public function backup()
    {
        try {
            Artisan::call('db:backup');
            Log::info('Manual DB Backup triggered by User ID: ' . auth()->id());
            return back()->with('success', 'Database backup completed successfully.');
        } catch (\Exception $e) {
            Log::error('Manual DB Backup failed: ' . $e->getMessage());
            return back()->with('error', 'Backup failed: ' . $e->getMessage());
        }
    }

    /**
     * Download a specific backup file.
     */
    public function download($filename)
    {
        $path = storage_path('app/backups/' . $filename);

        if (!File::exists($path)) {
            return back()->with('error', 'Backup file not found.');
        }

        return response()->download($path);
    }

    /**
     * Import a database backup.
     * Restricted to a single predefined user ID and requires special credentials.
     */
    public function import(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:sql,txt',
            'import_username' => 'required|string',
            'import_password' => 'required|string',
        ]);

        // Security Check 1: User ID Restriction
        $allowedUserId = (int) env('DB_IMPORT_USER_ID', 1);
        if (auth()->id() !== $allowedUserId) {
            Log::warning('Unauthorized DB import attempt by User ID: ' . auth()->id());
            return back()->with('error', 'You are not authorized to import database backups.');
        }

        // Security Check 2: Special Credentials
        $expectedUsername = env('DB_IMPORT_USERNAME', 'admin');
        $expectedPassword = env('DB_IMPORT_PASSWORD', 'importpass123');

        if ($request->import_username !== $expectedUsername || $request->import_password !== $expectedPassword) {
            Log::warning('Failed DB import authentication attempt by User ID: ' . auth()->id());
            return back()->with('error', 'Invalid import credentials.');
        }

        $file = $request->file('import_file');
        $tempPath = $file->storeAs('temp', 'import_temp.sql');
        $absoluteTempPath = Storage::disk('local')->path($tempPath);

        // Retrieve DB credentials
        $dbHost = config('database.connections.mysql.host');
        $dbPort = config('database.connections.mysql.port', 3306);
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');
        $dbName = config('database.connections.mysql.database');

        // Execute MySQL import
        $mysqlPath = env('DB_MYSQL_PATH', 'mysql');
        // Convert backslashes to forward slashes to prevent MySQL escape character issues on Windows
        $normalizedPath = str_replace('\\', '/', $absoluteTempPath);
        $command = [$mysqlPath, '-u', $dbUser, '-h', $dbHost, '-P', $dbPort, $dbName, '-e', 'source ' . $normalizedPath];

        $process = new Process($command);
        $process->setTimeout(600); // 10 minutes max for large imports

        $env = array_merge(getenv(), $_SERVER, $_ENV);
        if (!empty($dbPass)) {
            $env['MYSQL_PWD'] = $dbPass;
        }
        $process->setEnv($env);

        try {
            $process->mustRun();
            Log::info('Database imported successfully by User ID: ' . auth()->id());

            // Cleanup temp file
            if (File::exists($absoluteTempPath)) {
                File::delete($absoluteTempPath);
            }

            return back()->with('success', 'Database imported successfully.');
        } catch (ProcessFailedException $exception) {
            Log::error('DB Import Failed: ' . $exception->getMessage());

            if (File::exists($absoluteTempPath)) {
                File::delete($absoluteTempPath);
            }

            return back()->with('error', 'Database import failed: ' . $exception->getMessage());
        }
    }

    /**
     * Delete a backup file.
     */
    public function destroy($filename)
    {
        $path = storage_path('app/backups/' . $filename);

        if (File::exists($path)) {
            File::delete($path);
            Log::info('Backup deleted: ' . $filename . ' by User ID: ' . auth()->id());
            return back()->with('success', 'Backup deleted successfully.');
        }

        return back()->with('error', 'Backup file not found.');
    }

    /**
     * Format bytes to human readable string.
     */
    private function humanFilesize($bytes, $decimals = 2) {
        $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
    }
}
