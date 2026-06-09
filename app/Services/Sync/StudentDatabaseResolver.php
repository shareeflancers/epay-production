<?php

namespace App\Services\Sync;

use App\Models\Consumer;
use App\Models\Level;
use App\Models\SchoolClass;

class StudentDatabaseResolver
{
    protected array $levelsMap = [];
    protected array $classesMap = [];

    public function __construct()
    {
        // Pre-fetch levels and classes for faster lookup
        $this->levelsMap = Level::pluck('id', 'level')->toArray();
        $this->classesMap = SchoolClass::pluck('id', 'name')->toArray();
    }

    /**
     * Map the educational level string to a DB ID.
     */
    public function resolveLevelId(string $levelName): int
    {
        if (!isset($this->levelsMap[$levelName])) {
            $newLevel = Level::create(['level' => $levelName, 'display_order' => count($this->levelsMap) + 1]);
            $this->levelsMap[$levelName] = $newLevel->id;
        }

        return $this->levelsMap[$levelName];
    }

    /**
     * Map the class name string to a DB ID.
     */
    public function resolveClassId(string $className): int
    {
        if (!isset($this->classesMap[$className])) {
            $newClass = SchoolClass::create(['name' => $className, 'display_order' => count($this->classesMap) + 1]);
            $this->classesMap[$className] = $newClass->id;
        }

        return $this->classesMap[$className];
    }

    /**
     * Check if a consumer with this B-Form exists in DB under a different consumer_number
     */
    public function hasDuplicateBform(string $bform, string $consumerNumber): bool
    {
        return Consumer::where('identification_number', $bform)
            ->where('consumer_type', 'student')
            ->where('consumer_number', '!=', $consumerNumber)
            ->exists();
    }

    /**
     * Check if a consumer with this Student ID exists in DB under a different consumer_number
     */
    public function hasDuplicateStudentId(string $sisStudentId, string $consumerNumber): bool
    {
        return Consumer::where('sis_student_id', $sisStudentId)
            ->where('consumer_type', 'student')
            ->where('consumer_number', '!=', $consumerNumber)
            ->exists();
    }
}
