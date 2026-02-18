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
            'institution' => 'https://sis.fgei.gov.pk/api/FetchAllInstitutions',
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

            DB::beginTransaction();

            switch ($type) {
                case 'student':
                    $stats = [
                        'inserted' => 0,
                        'updated' => 0,
                        'unchanged' => 0,
                    ];

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

                        $validated['consumer_number'] = $validated['s_region_idFk'] . $validated['s_school_idFk'] .   str_pad($validated['s_id'], 6, '0', STR_PAD_LEFT);

                        // 1. Process Consumer
                        $consumer = Consumer::firstOrNew(['identification_number' => $validated['std_form_b']]);
                        $consumer->fill([
                            'consumer_type' => 'student',
                            'consumer_number' => $validated['consumer_number'],
                            'institution_id' => $validated['s_school_idFk'],
                            'region_id' => $validated['s_region_idFk'],
                            'is_active' => 1,
                        ]);

                        $consumerIsDirty = $consumer->isDirty();
                        $consumer->save();
                        $consumerWasCreated = $consumer->wasRecentlyCreated;

                        // 2. Process ProfileDetail
                        $profile = ProfileDetail::firstOrNew([
                            'consumer_id' => $consumer->id,
                            'profile_type' => 'student'
                        ]);
                        $profile->fill([
                            'name' => $validated['s_name'],
                            'father_or_guardian_name' => $validated['father_or_guardian_name'],
                            'region_name' => $validated['region_name'],
                            'institution_name' => $validated['institution_name'],
                            'institution_level' => $validated['educational_level'],
                            'class' => $validated['class_name'],
                            'section' => $validated['section_name'],
                            'is_active' => 1,
                        ]);

                        $profileIsDirty = $profile->isDirty();
                        $profile->save();
                        $profileWasCreated = $profile->wasRecentlyCreated;

                        // 3. Update stats
                        if ($consumerWasCreated || $profileWasCreated) {
                            $stats['inserted']++;
                        } elseif ($consumerIsDirty || $profileIsDirty) {
                            $stats['updated']++;
                        } else {
                            $stats['unchanged']++;
                        }
                    }

                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'message' => 'Data processed successfully',
                        'data' => $decryptedData,
                        'stats' => $stats
                    ]);
                    break;
                // case 'institution':
                //     foreach ($decryptedData as $decrypted) {
                //         $validator = Validator::make((array) $decrypted, [
                //             's_school_idFk' => 'required|integer|digits_between:1,3',
                //             's_region_idFk' => 'required|integer|digits_between:1,3',
                //             'std_form_b' => 'required|integer|digits_between:1,13',
                //             'region_name' => 'required|string|max:255',
                //             'institution_name' => 'required|string|max:255',
                //             'educational_level' => 'required|string|max:255',
                //         ]);

                //         if ($validator->fails()) {
                //             DB::rollBack();
                //             return response()->json([
                //                 'success' => false,
                //                 'message' => 'Validation failed',
                //                 'errors' => $validator->errors()
                //             ], 422);
                //         }

                //         $validated = $validator->validated();

                //         $validated['consumer_number'] = $validated['std_form_b'];

                //         // Create or update Consumer
                //         $consumer = Consumer::updateOrCreate(
                //             ['identification_number' => $validated['std_form_b']], // Search criteria
                //             [
                //                 'consumer_type' => 'institution',
                //                 'consumer_number' => $validated['consumer_number'],
                //                 'institution_id' => $validated['s_school_idFk'],
                //                 'region_id' => $validated['s_region_idFk'],
                //                 'is_active' => 1,
                //             ]
                //         );

                //         // Create or update ProfileDetail linked to the Consumer
                //         ProfileDetail::updateOrCreate(
                //             [
                //                 'consumer_id' => $consumer->id,
                //                 'profile_type' => 'institution'
                //             ],
                //             [
                //                 'region_name' => $validated['region_name'],
                //                 'institution_name' => $validated['institution_name'],
                //                 'institution_level' => $validated['educational_level'],
                //                 'is_active' => 1,
                //             ]
                //         );
                //     }
                //     break;
                default:
                    break;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $decryptedData
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to process data: ' . $e->getMessage()
            ], 500);
        }
    }
}
