<?php

namespace App\Services;

use App\Models\ProcedureSnapshot;
use App\Models\ActiveChallan;
use App\Models\ChallanHistory;
use App\Models\Consumer;
use App\Models\ProfileDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcedureService
{
    /**
     * Create a snapshot before Step 1 (Archive)
     * To prevent "MySQL server has gone away" due to large payloads (like challan_snapshot JSON blobs),
     * we only store the list of challan numbers that are being archived.
     */
    public static function snapshotArchive()
    {
        $challanNos = ActiveChallan::pluck('challan_no')->toArray();
        return ProcedureSnapshot::create([
            'step_name' => 'archive',
            'snapshot_data' => [
                'challan_nos' => $challanNos
            ],
            'batch_id' => 'batch_' . time(),
        ]);
    }

    /**
     * Create a snapshot before Step 2 (Sync)
     * For Sync, we snapshot the current state of consumers and profiles that might be updated.
     */
    public static function snapshotSync()
    {
        // Snapshot everything is too large, but for 1-click rollback of sync,
        // we might want to just store the "state before sync" for existing consumers.
        // For simplicity in this demo, let's store the count and a timestamp to allow "undoing" new entries.
        $data = [
            'before_count' => Consumer::count(),
            'timestamp' => now()->toDateTimeString(),
        ];

        return ProcedureSnapshot::create([
            'step_name' => 'sync',
            'snapshot_data' => $data,
            'batch_id' => 'batch_' . time(),
        ]);
    }

    /**
     * Create a snapshot before Step 4 (Sync Institutions)
     */
    public static function snapshotSyncInstitutions()
    {
        $data = [
            'before_count' => Consumer::where('consumer_type', 'institution')->count(),
            'timestamp' => now()->toDateTimeString(),
        ];

        return ProcedureSnapshot::create([
            'step_name' => 'sync_institutions',
            'snapshot_data' => $data,
            'batch_id' => 'batch_' . time(),
        ]);
    }

    /**
     * Create a snapshot before Step 3 (Generate)
     */
    public static function snapshotGenerate()
    {
        // For generation, we just need to know which challans were created in this batch to delete them.
        return ProcedureSnapshot::create([
            'step_name' => 'generate',
            'snapshot_data' => ['timestamp' => now()->toDateTimeString()],
            'batch_id' => 'batch_' . time(),
        ]);
    }

    /**
     * Rollback a specific snapshot
     */
    public static function rollback($snapshotId)
    {
        Log::info("Starting rollback for snapshot ID: {$snapshotId}");
        $snapshot = ProcedureSnapshot::findOrFail($snapshotId);

        if ($snapshot->is_rolled_back) {
            throw new \Exception('This procedure has already been rolled back.');
        }

        DB::beginTransaction();
        try {
            switch ($snapshot->step_name) {
                case 'archive':
                    self::rollbackArchive($snapshot);
                    break;
                case 'sync':
                    self::rollbackSync($snapshot);
                    break;
                case 'sync_institutions':
                    self::rollbackSyncInstitutions($snapshot);
                    break;
                case 'generate':
                    self::rollbackGenerate($snapshot);
                    break;
            }

            $snapshot->update(['is_rolled_back' => true]);
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Rollback failed: ' . $e->getMessage());
            throw $e;
        }
    }

    private static function rollbackArchive($snapshot)
    {
        $data = $snapshot->snapshot_data;
        $challanNos = $data['challan_nos'] ?? [];

        if (empty($challanNos)) {
            return;
        }

        // Restore from ChallanHistory using the archived challan numbers
        $historyRecords = ChallanHistory::whereIn('challan_no', $challanNos)->get();
        foreach ($historyRecords as $record) {
            $attributes = $record->getAttributes();
            unset($attributes['id']); // Exclude primary key

            ActiveChallan::create($attributes);
            $record->delete();
        }
    }

    private static function rollbackSync($snapshot)
    {
        $timestamp = $snapshot->snapshot_data['timestamp'];

        // Delete consumers created AFTER the sync started
        $newConsumers = Consumer::where('created_at', '>=', $timestamp)->get();
        foreach ($newConsumers as $c) {
            ProfileDetail::where('consumer_id', $c->id)->delete();
            $c->delete();
        }

        // Note: Reverting UPDATED records is complex without a full row-by-row snapshot.
        // For now, we focus on removing the new batch.
    }

    private static function rollbackSyncInstitutions($snapshot)
    {
        $timestamp = $snapshot->snapshot_data['timestamp'];

        // Delete institution consumers created AFTER the sync started
        $newConsumers = Consumer::where('consumer_type', 'institution')
                                ->where('created_at', '>=', $timestamp)
                                ->get();
        foreach ($newConsumers as $c) {
            ProfileDetail::where('consumer_id', $c->id)->delete();
            $c->delete();
        }
    }

    private static function rollbackGenerate($snapshot)
    {
        $timestamp = $snapshot->snapshot_data['timestamp'];
        // Delete challans created in this generation batch
        ActiveChallan::where('created_at', '>=', $timestamp)->delete();
    }
}
