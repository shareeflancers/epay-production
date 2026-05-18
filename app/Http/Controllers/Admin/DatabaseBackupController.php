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
            return redirect()->back()->with('success', 'Database backup completed successfully.');
        } catch (\Exception $e) {
            Log::error('Manual DB Backup failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Backup failed: ' . $e->getMessage());
        }
    }

    /**
     * Download a specific backup file.
     */
    public function download($filename)
    {
        $path = storage_path('app/backups/' . $filename);

        if (!File::exists($path)) {
            return redirect()->back()->with('error', 'Backup file not found.');
        }

        return response()->download($path);
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
            return redirect()->back()->with('success', 'Backup deleted successfully.');
        }

        return redirect()->back()->with('error', 'Backup file not found.');
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
