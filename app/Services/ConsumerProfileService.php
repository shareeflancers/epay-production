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

        // Process Consumer
        $consumer = Consumer::firstOrNew($consumerKeys);
        $consumer->fill($consumerData);
        $consumerIsDirty = $consumer->isDirty();
        $consumer->save();
        $consumerWasCreated = $consumer->wasRecentlyCreated;

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
