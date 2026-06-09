<?php

namespace App\Services\Sync\Actions;

class UnchangedAction
{
    /**
     * Handle logic for an unchanged record.
     *
     * @param array $validated The validated student data
     * @return array|null The report entry, or null if unchanged records aren't reported
     */
    public function execute(array $validated): ?array
    {
        // Future: Add any logic for unchanged records here
        
        // Unchanged records typically don't bloat the CSV report, 
        // so we return null. If we want them later, return an array here.
        return null;
    }
}
