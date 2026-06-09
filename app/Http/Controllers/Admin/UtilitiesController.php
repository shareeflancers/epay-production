<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\Sync\FetchAndDecryptService;
use App\Services\Sync\StudentSyncProcessor;
use App\Services\ProcedureService;
use Illuminate\Validation\ValidationException;

class UtilitiesController extends Controller
{
    protected FetchAndDecryptService $fetchService;
    protected StudentSyncProcessor $studentProcessor;

    public function __construct(
        FetchAndDecryptService $fetchService,
        StudentSyncProcessor $studentProcessor
    ) {
        $this->fetchService = $fetchService;
        $this->studentProcessor = $studentProcessor;
    }

    public function apiFetch($type)
    {
        try {
            // 1. Fetch and Decrypt
            $decryptedData = $this->fetchService->fetchAndDecrypt($type);

            DB::beginTransaction();

            // 2. Pre-sync Snapshot
            if ($type === 'institution') {
                ProcedureService::snapshotSyncInstitutions();
            } elseif ($type === 'student') {
                ProcedureService::snapshotSync();
            }

            // 3. Process based on type
            switch ($type) {
                case 'student':
                    try {
                        $result = $this->studentProcessor->process($decryptedData);

                        DB::commit();

                        return response()->json([
                            'success' => true,
                            'message' => 'Data processed successfully',
                            'data' => $decryptedData,
                            'stats' => $result['stats'],
                            'report' => $result['report']
                        ]);

                    } catch (ValidationException $e) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Validation failed',
                            'errors' => $e->validator->errors()
                        ], 422);
                    }
                    break;

                default:
                    // Other types like 'inductee' can be added here
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
