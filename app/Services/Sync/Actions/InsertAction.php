<?php

namespace App\Services\Sync\Actions;

class InsertAction
{
    /**
     * Handle logic and generate report for an inserted record.
     *
     * @param array $validated The validated student data
     * @return array The report entry
     */
    public function execute(array $validated): array
    {
        // Future: Add any post-insert specific business logic here 
        // (e.g., sending welcome emails, triggering SMS, creating default challans)

        return [
            'name' => $validated['s_name'],
            'bform' => $validated['std_form_b'],
            'status' => 'Added',
            'reason' => 'New student record created',
        ];
    }
}
