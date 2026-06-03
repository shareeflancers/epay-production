<?php

namespace App\Services;

use App\Models\Consumer;
use App\Models\ProfileDetail;

class ConsumerProfileService
{
    /**
     * Syncs consumer and profile detail records.
     * 
     * @param array $consumerKeys
     * @param array $consumerData
     * @param array $profileKeys
     * @param array $profileData
     * @return array The resulting statistics change (inserted, updated, unchanged)
     */
    public function syncConsumerAndProfile(array $consumerKeys, array $consumerData, array $profileKeys, array $profileData): array
    {
        $stats = ['inserted' => 0, 'updated' => 0, 'unchanged' => 0];

        $consumer = null;

        // Smart lookup for students to prevent duplicates
        if (($consumerKeys['consumer_type'] ?? null) === 'student' || ($consumerData['consumer_type'] ?? null) === 'student') {
            $sisStudentId = $consumerKeys['sis_student_id'] ?? $consumerData['sis_student_id'] ?? null;
            $consumerNumber = $consumerKeys['consumer_number'] ?? $consumerData['consumer_number'] ?? null;

            if ($sisStudentId) {
                $consumer = Consumer::where('sis_student_id', $sisStudentId)->where('consumer_type', 'student')->first();
            }
            if (!$consumer && $consumerNumber) {
                $consumer = Consumer::where('consumer_number', $consumerNumber)->where('consumer_type', 'student')->first();
            }
        }

        // Default fallback lookup
        if (!$consumer) {
            $consumer = Consumer::firstOrNew($consumerKeys);
        }

        $consumer->fill($consumerData);
        $consumerIsDirty = $consumer->isDirty();
        $cnicWasChanged = $consumer->isDirty('identification_number');
        $consumer->save();
        $consumerWasCreated = $consumer->wasRecentlyCreated;

        if ($cnicWasChanged && !$consumerWasCreated) {
            $newBform = $consumer->identification_number;

            // Update active challans
            \App\Models\ActiveChallan::where('consumer_id', $consumer->id)
                ->whereNotNull('challan_snapshot')
                ->chunkById(100, function ($challans) use ($newBform) {
                    foreach ($challans as $challan) {
                        $snap = json_decode($challan->challan_snapshot, true);
                        if (isset($snap['consumer'])) {
                            $snap['consumer']['identification_number'] = $newBform;
                            $challan->challan_snapshot = json_encode($snap);
                            $challan->save();
                        }
                    }
                });

            // Update history
            \App\Models\ChallanHistory::where('consumer_id', $consumer->id)
                ->whereNotNull('challan_snapshot')
                ->chunkById(100, function ($histories) use ($newBform) {
                    foreach ($histories as $history) {
                        $snap = json_decode($history->challan_snapshot, true);
                        if (isset($snap['consumer'])) {
                            $snap['consumer']['identification_number'] = $newBform;
                            $history->challan_snapshot = json_encode($snap);
                            $history->save();
                        }
                    }
                });
        }

        // Process ProfileDetail
        $profileKeys['consumer_id'] = $consumer->id; // Ensure foreign key is set
        $profile = ProfileDetail::firstOrNew($profileKeys);
        $profile->fill($profileData);
        $profileIsDirty = $profile->isDirty();
        $profile->save();
        $profileWasCreated = $profile->wasRecentlyCreated;

        if ($consumerWasCreated || $profileWasCreated) {
            $stats['inserted'] = 1;
        } elseif ($consumerIsDirty || $profileIsDirty) {
            $stats['updated'] = 1;
        } else {
            $stats['unchanged'] = 1;
        }

        return $stats;
    }
}
