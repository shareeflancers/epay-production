<?php

namespace App\Services\Sync;

use App\Services\ConsumerProfileService;
use App\Services\Sync\Actions\SkipAction;
use App\Services\Sync\Actions\InsertAction;
use App\Services\Sync\Actions\UpdateAction;
use App\Services\Sync\Actions\UnchangedAction;
use Illuminate\Validation\ValidationException;

class StudentSyncProcessor
{
    protected StudentValidationService $validationService;
    protected StudentDatabaseResolver $dbResolver;
    protected ConsumerProfileService $syncService;

    // Actions
    protected SkipAction $skipAction;
    protected InsertAction $insertAction;
    protected UpdateAction $updateAction;
    protected UnchangedAction $unchangedAction;

    public function __construct(
        StudentValidationService $validationService,
        StudentDatabaseResolver $dbResolver,
        ConsumerProfileService $syncService,
        SkipAction $skipAction,
        InsertAction $insertAction,
        UpdateAction $updateAction,
        UnchangedAction $unchangedAction
    ) {
        $this->validationService = $validationService;
        $this->dbResolver = $dbResolver;
        $this->syncService = $syncService;

        $this->skipAction = $skipAction;
        $this->insertAction = $insertAction;
        $this->updateAction = $updateAction;
        $this->unchangedAction = $unchangedAction;
    }

    /**
     * Process an array of raw student data.
     *
     * @param iterable $decryptedData
     * @return array Contains 'stats' and 'report'
     */
    public function process(iterable $decryptedData): array
    {
        $stats = [
            'inserted' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'skipped' => 0,
        ];
        $report = [];
        $processedBforms = [];

        foreach ($decryptedData as $rawStudent) {
            try {
                // 1. Validation
                $validated = $this->validationService->validate((array) $rawStudent);
            } catch (ValidationException $e) {
                // If core validation fails, we can either throw it up (rolling back batch)
                // or skip it. Current system logic rolls back on strict validation failure.
                throw $e;
            }

            // 2. Pre-database Skip Conditions
            if (empty($validated['fee_fund_category_ids'])) {
                $stats['skipped']++;
                $report[] = $this->skipAction->execute($validated, 'No valid fee category assigned');
                continue;
            }

            if (in_array($validated['std_form_b'], $processedBforms)) {
                $stats['skipped']++;
                $report[] = $this->skipAction->execute($validated, 'Duplicate B-form in current batch');
                continue;
            }

            // 3. Database Duplicate Checks
            if ($this->dbResolver->hasDuplicateBform($validated['std_form_b'], $validated['consumer_number']) ||
                $this->dbResolver->hasDuplicateStudentId($validated['s_id'], $validated['consumer_number'])) {

                $stats['skipped']++;
                $report[] = $this->skipAction->execute($validated, 'Duplicate B-form or Student ID already exists for another consumer');
                continue;
            }

            $processedBforms[] = $validated['std_form_b'];

            // 4. Resolve DB mapping for classes/levels
            $levelId = $this->dbResolver->resolveLevelId($validated['educational_level']);
            $classId = $this->dbResolver->resolveClassId($validated['class_name']);

            // 5. Prepare Payload for Upsert
            $consumerKeys = [
                'consumer_number' => $validated['consumer_number'],
                'consumer_type'  => 'student',
            ];
            $consumerData = [
                'identification_number' => $validated['std_form_b'],
                'sis_student_id' => $validated['s_id'],
                'institution_id' => $validated['s_school_idFk'],
                'region_id' => $validated['s_region_idFk'],
                'is_active' => 1,
            ];

            $profileKeys = ['profile_type' => 'student'];
            $profileData = [
                'name' => ucwords(strtolower($validated['s_name'])),
                'father_or_guardian_name' => ucwords(strtolower($validated['father_or_guardian_name'])),
                'region_name' => $validated['region_name'],
                'institution_name' => $validated['institution_name'],
                'institution_level' => $validated['educational_level'],
                'level_id' => $levelId,
                'class' => $validated['class_name'],
                'school_class_id' => $classId,
                'section' => $validated['section_name'],
                'fee_fund_category_ids' => $validated['fee_fund_category_ids'],
                'is_active' => 1,
            ];

            // 6. Execute Upsert via existing Sync Service
            $resultStats = $this->syncService->syncConsumerAndProfile($consumerKeys, $consumerData, $profileKeys, $profileData);

            // 7. Route to proper State Action based on Result
            if ($resultStats['inserted']) {
                $stats['inserted']++;
                $reportItem = $this->insertAction->execute($validated);
                if ($reportItem) {
                    $report[] = $reportItem;
                }
            } elseif ($resultStats['updated']) {
                $stats['updated']++;
                $changes = $resultStats['changes'] ?? [];
                $reportItem = $this->updateAction->execute($validated, $changes);
                if ($reportItem) {
                    $report[] = $reportItem;
                }
            } else {
                $stats['unchanged']++;
                $reportItem = $this->unchangedAction->execute($validated);
                if ($reportItem) {
                    $report[] = $reportItem;
                }
            }
        }

        return [
            'stats' => $stats,
            'report' => $report,
        ];
    }
}
