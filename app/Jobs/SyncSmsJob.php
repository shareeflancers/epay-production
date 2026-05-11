<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Database\Eloquent\Model;

class SyncSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $challan;

    /**
     * Create a new job instance.
     */
    public function __construct(Model $challan)
    {
        $this->challan = $challan;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        \App\Services\SmsSyncService::syncPaidChallan($this->challan);
    }
}
