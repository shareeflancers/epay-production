<?php

namespace App\Services\Sync\Actions;

class DeactivateAction
{
    /**
     * Generate the report array for a deactivated record.
     *
     * @param string $name The student's name
     * @param string $bform The student's B-form
     * @param string $reason The reason for deactivation
     * @return array The report entry
     */
    public function execute(string $name, string $bform, string $reason): array
    {
        return [
            'name' => $name,
            'bform' => $bform,
            'status' => 'Deactivated',
            'reason' => $reason,
        ];
    }
}
