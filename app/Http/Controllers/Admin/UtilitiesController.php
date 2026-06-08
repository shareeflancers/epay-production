<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Encryption\Encrypter;
use App\Models\Consumer;
use App\Models\ProfileDetail;
use App\Models\FeeFundCategory;
use App\Models\Level;
use App\Models\SchoolClass;
use App\Models\Institution;

class UtilitiesController extends Controller
{
    /**
     * The encrypter instance using the external SIS key.
     */
    protected $encrypter;

    public function __construct()
    {
        $key = env('ENCRYPTION');

        // The SIS app uses Crypt::encrypt() with a base64-encoded APP_KEY
        $decodedKey = base64_decode($key);

        // Determine cipher based on key length (16 bytes = AES-128, 32 bytes = AES-256)
        $cipher = strlen($decodedKey) === 16 ? 'aes-128-cbc' : 'aes-256-cbc';

        $this->encrypter = new Encrypter($decodedKey, $cipher);
    }

    public function apiFetch($type)
    {
        $endpoints = [
            'student'     => 'https://sis.fgei.gov.pk/api/FetchAllStudents',
            'institution' => 'https://hrms.fgei.gov.pk/api/FetchInstitutions',
            'inductee'    => 'https://induction.fgei.gov.pk/api/FetchAllInductees',
        ];

        if (!isset($endpoints[$type])) {
            return response()->json(['success' => false, 'message' => 'Invalid fetch type'], 400);
        }

        $response = Http::get($endpoints[$type]);

        if (!$response->successful()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch data from API'
            ], $response->status());
        }

        try {
            $encryptedData = $response->json('data');
            $decryptedData = $this->encrypter->decrypt($encryptedData);

            if (is_string($decryptedData)) {
                $decryptedData = json_decode($decryptedData, true);
            }

            DB::beginTransaction();

            // Take snapshot before processing sync
            if ($type === 'institution') {
                \App\Services\ProcedureService::snapshotSyncInstitutions();
            } elseif ($type === 'student') {
                \App\Services\ProcedureService::snapshotSync();
            }

            $syncService = app(\App\Services\ConsumerProfileService::class);

            switch ($type) {
                case 'student':
                    $stats = [
                        'inserted' => 0,
                        'updated' => 0,
                        'unchanged' => 0,
                        'skipped' => 0,
                    ];
                    $report = [];

                    $processedBforms = [];

                    $validCategoryIds = FeeFundCategory::pluck('id')->toArray();

                    // Pre-fetch levels and classes for faster lookup
                    $levelsMap = Level::pluck('id', 'level')->toArray();
                    $classesMap = SchoolClass::pluck('id', 'name')->toArray();

                    foreach ($decryptedData as $decrypted) {
                        $validator = Validator::make((array) $decrypted, [
                            's_id' => 'required|integer|digits_between:1,6',
                            's_school_idFk' => 'required|integer|digits_between:1,3',
                            's_region_idFk' => 'required|integer|digits_between:1,3',
                            'std_form_b' => 'required|integer|digits_between:1,13',
                            's_name' => 'required|string|max:255',
                            'father_or_guardian_name' => 'required|string|max:255',
                            'region_name' => 'required|string|max:255',
                            'institution_name' => 'required|string|max:255',
                            'educational_level' => 'required|string|max:255',
                            'section_name' => 'required|string|max:255',
                            'class_name' => 'required|string|max:255',
                            'fee_category' => 'nullable|string',
                        ]);

                        if ($validator->fails()) {
                            DB::rollBack();
                            return response()->json([
                                'success' => false,
                                'message' => 'Validation failed',
                                'errors' => $validator->errors()
                            ], 422);
                        }

                        $validated = $validator->validated();

                        $region = str_pad($validated['s_region_idFk'], 2, '0', STR_PAD_LEFT);
                        $school = str_pad($validated['s_school_idFk'], 3, '0', STR_PAD_LEFT);
                        $id     = str_pad($validated['s_id'], 6, '0', STR_PAD_LEFT);

                        $validated['consumer_number'] = $region . $school . $id;

                        $feeFundCategoryIds = null;
                        if (!empty($validated['fee_category'])) {
                            $decodedCategories = json_decode($validated['fee_category'], true);

                            if (is_array($decodedCategories) && count($decodedCategories) > 0) {
                                if (in_array(1, $decodedCategories) && in_array(4, $decodedCategories)) {
                                     $decodedCategories = array_diff($decodedCategories, [4]);
                                }
                                $validIds = array_values(array_intersect($decodedCategories, $validCategoryIds));
                                $feeFundCategoryIds = count($validIds) > 0 ? $validIds : null;
                            }
                        }

                        if (empty($feeFundCategoryIds)) {
                            $stats['skipped']++;
                            $report[] = [
                                'name' => $validated['s_name'],
                                'bform' => $validated['std_form_b'],
                                'status' => 'Skipped',
                                'reason' => 'No valid fee category assigned',
                            ];
                            continue;
                        }

                        // Map Level
                        $levelName = $validated['educational_level'];
                        if (!isset($levelsMap[$levelName])) {
                            $newLevel = Level::create(['level' => $levelName, 'display_order' => count($levelsMap) + 1]);
                            $levelsMap[$levelName] = $newLevel->id;
                        }
                        $levelId = $levelsMap[$levelName];

                        // Map Class
                        $className = $validated['class_name'];
                        if (!isset($classesMap[$className])) {
                            $newClass = SchoolClass::create(['name' => $className, 'display_order' => count($classesMap) + 1]);
                            $classesMap[$className] = $newClass->id;
                        }
                        $classId = $classesMap[$className];

                        // Check for duplicate B-form (CNIC) or sis_student_id to satisfy strict unique constraints
                        if (in_array($validated['std_form_b'], $processedBforms)) {
                            $stats['skipped']++;
                            $report[] = [
                                'name' => $validated['s_name'],
                                'bform' => $validated['std_form_b'],
                                'status' => 'Skipped',
                                'reason' => 'Duplicate B-form in current batch',
                            ];
                            continue;
                        }

                        $duplicateCnicExists = Consumer::where('identification_number', $validated['std_form_b'])
                            ->where('consumer_type', 'student')
                            ->where('consumer_number', '!=', $validated['consumer_number'])
                            ->exists();

                        $duplicateStudentIdExists = Consumer::where('sis_student_id', $validated['s_id'])
                            ->where('consumer_type', 'student')
                            ->where('consumer_number', '!=', $validated['consumer_number'])
                            ->exists();

                        if ($duplicateCnicExists || $duplicateStudentIdExists) {
                            $stats['skipped']++;
                            $report[] = [
                                'name' => $validated['s_name'],
                                'bform' => $validated['std_form_b'],
                                'status' => 'Skipped',
                                'reason' => 'Duplicate B-form or Student ID already exists for another consumer',
                            ];
                            continue;
                        }

                        $processedBforms[] = $validated['std_form_b'];

                        // 1. Process Consumer
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

                        // 2. Process ProfileDetail
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
                            'fee_fund_category_ids' => $feeFundCategoryIds,
                            'is_active' => 1,
                        ];

                        $resultStats = $syncService->syncConsumerAndProfile($consumerKeys, $consumerData, $profileKeys, $profileData);
                        $stats['inserted'] += $resultStats['inserted'];
                        $stats['updated'] += $resultStats['updated'];
                        $stats['unchanged'] += $resultStats['unchanged'];

                        if ($resultStats['inserted']) {
                            $report[] = [
                                'name' => $validated['s_name'],
                                'bform' => $validated['std_form_b'],
                                'status' => 'Added',
                                'reason' => 'New student record created',
                            ];
                        } elseif ($resultStats['updated']) {
                            $report[] = [
                                'name' => $validated['s_name'],
                                'bform' => $validated['std_form_b'],
                                'status' => 'Updated',
                                'reason' => 'Student record updated',
                            ];
                        }
                    }

                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'message' => 'Data processed successfully',
                        'data' => $decryptedData,
                        'stats' => $stats,
                        'report' => $report
                    ]);
                    break;
                case 'institution':
                    $stats = [
                        'inserted' => 0,
                        'updated' => 0,
                        'unchanged' => 0,
                    ];

                    $levelsMap = Level::pluck('id', 'level')->toArray();

                    foreach ($decryptedData as $decrypted) {
                        $validator = Validator::make((array) $decrypted, [
                            's_school_idFk' => 'required|integer|digits_between:1,3',
                            's_region_idFk' => 'required|integer|digits_between:1,3',
                            'region_name' => 'required|string|max:255',
                            'institution_name' => 'required|string|max:255',
                            'educational_level' => 'required|string|max:255',
                            'principal_name' => 'required|string|max:255',
                            'principal_cnic' => 'required|integer|digits_between:1,13',
                        ]);

                        if ($validator->fails()) {
                            DB::rollBack();
                            return response()->json([
                                'success' => false,
                                'message' => 'Validation failed',
                                'errors' => $validator->errors()
                            ], 422);
                        }

                        $validated = $validator->validated();

                        $regionPadded = str_pad($validated['s_region_idFk'], 2, '0', STR_PAD_LEFT);
                        $instPadded = str_pad($validated['s_school_idFk'], 3, '0', STR_PAD_LEFT);

                        $consumerNumber = $regionPadded . '000000' . $instPadded;
                        $identificationNumber = $regionPadded . '00000000' . $instPadded;

                        // Map Level
                        $levelName = $validated['educational_level'];
                        if (!isset($levelsMap[$levelName])) {
                            $newLevel = Level::create(['level' => $levelName, 'display_order' => count($levelsMap) + 1]);
                            $levelsMap[$levelName] = $newLevel->id;
                        }
                        $levelId = $levelsMap[$levelName];

                        // 1. Process Institution Model
                        Institution::updateOrCreate(
                            ['id' => $validated['s_school_idFk']],
                            [
                                'name' => $validated['institution_name'],
                                'region_id' => $validated['s_region_idFk'],
                                'level_id' => $levelId,
                                'principal_name' => ucwords(strtolower($validated['principal_name'])),
                                'principal_cnic' => $validated['principal_cnic'],
                                'is_active' => 1,
                            ]
                        );

                        // 2. Process Consumer
                        $consumerKeys = [
                            'institution_id' => $validated['s_school_idFk'],
                            'consumer_type'  => 'institution',
                        ];
                        $consumerData = [
                            'identification_number' => $identificationNumber,
                            'consumer_number' => $consumerNumber,
                            'region_id' => $validated['s_region_idFk'],
                            'is_active' => 1,
                        ];

                        // 3. Process ProfileDetail
                        $profileKeys = ['profile_type' => 'institution'];
                        $profileData = [
                            'name' => ucwords(strtolower($validated['principal_name'])),
                            'region_name' => $validated['region_name'],
                            'institution_name' => $validated['institution_name'],
                            'institution_level' => $validated['educational_level'],
                            'institution_id' => $validated['s_school_idFk'],
                            'level_id' => $levelId,
                            'region_id' => $validated['s_region_idFk'],
                            'is_active' => 1,
                        ];

                        $resultStats = $syncService->syncConsumerAndProfile($consumerKeys, $consumerData, $profileKeys, $profileData);
                        $stats['inserted'] += $resultStats['inserted'];
                        $stats['updated'] += $resultStats['updated'];
                        $stats['unchanged'] += $resultStats['unchanged'];
                    }

                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'message' => 'Data processed successfully',
                        'data' => $decryptedData,
                        'stats' => $stats
                    ]);
                    break;
                default:
                    break;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $decryptedData
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to process data: ' . $e->getMessage()
            ], 500);
        }
    }
}
