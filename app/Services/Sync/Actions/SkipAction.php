<?php

namespace App\Services\Sync\Actions;

class SkipAction
{
    /**
     * Generate the report array for a skipped record.
     *
     * @param array $validated The validated student data
     * @param string $reason The reason for skipping
     * @return array The report entry
     */
    public function execute(array $validated, string $reason): array
    {
        return [
            'name' => $validated['s_name'],
            'bform' => $validated['std_form_b'],
            'status' => 'Skipped',
            'reason' => $reason,
        ];
    }
}
