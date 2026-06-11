<?php

namespace App\Services\Sync\Actions;

class ReactivateAction
{
    /**
     * Generate the report array for a reactivated record.
     *
     * @param array $validated The validated student data
     * @return array The report entry
     */
    public function execute(array $validated): array
    {
        return [
            'name' => $validated['s_name'] ?? 'Unknown',
            'bform' => $validated['std_form_b'] ?? 'Unknown',
            'status' => 'Reactivated',
            'reason' => 'Student was inactive but found in latest sync',
        ];
    }
}
