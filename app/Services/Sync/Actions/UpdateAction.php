<?php

namespace App\Services\Sync\Actions;

class UpdateAction
{
    /**
     * Handle logic and generate report for an updated record.
     *
     * @param array $validated The validated student data
     * @return array The report entry
     */
    public function execute(array $validated, array $changes = []): array
    {
        // Future: Add any post-update specific business logic here 
        // (e.g., notifying external systems of profile changes)

        $reason = 'Student record updated';
        if (!empty($changes)) {
            // Optional: Map internal DB columns to user-friendly names if desired
            $reason .= ' (Fields changed: ' . implode(', ', array_unique($changes)) . ')';
        }

        return [
            'name' => $validated['s_name'],
            'bform' => $validated['std_form_b'],
            'status' => 'Updated',
            'reason' => $reason,
        ];
    }
}
