<?php

namespace App\Services\Sync;

use Illuminate\Support\Facades\Http;
use Illuminate\Encryption\Encrypter;

class FetchAndDecryptService
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

    /**
     * Fetch and decrypt data from external APIs based on type.
     *
     * @param string $type The sync type (e.g., student, institution, inductee)
     * @return iterable The decrypted data payload
     * @throws \Exception If fetch fails or decryption fails
     */
    public function fetchAndDecrypt(string $type): iterable
    {
        $endpoints = [
            'student'     => 'https://sis.fgei.gov.pk/api/FetchAllStudents',
            'institution' => 'https://hrms.fgei.gov.pk/api/FetchInstitutions',
            'inductee'    => 'https://induction.fgei.gov.pk/api/FetchAllInductees',
        ];

        if (!isset($endpoints[$type])) {
            throw new \Exception('Fetch type not found');
        }

        $response = Http::get($endpoints[$type]);

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch data from API. HTTP Status: ' . $response->status());
        }

        $encryptedData = $response->json('data');
        if (!$encryptedData) {
            throw new \Exception('No encrypted data payload found in response.');
        }

        $decryptedData = $this->encrypter->decrypt($encryptedData);

        if (is_string($decryptedData)) {
            $decryptedData = json_decode($decryptedData, true);
        }

        if (!is_iterable($decryptedData)) {
            throw new \Exception('Decrypted data is not iterable (expected array or Collection).');
        }

        return $decryptedData;
    }
}
