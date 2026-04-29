<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class GeoService
{
    /**
     * Resolve location details for an IP address.
     */
    public static function resolve($ip)
    {
        // Skip local/reserved IPs
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return [
                'country' => 'Localhost',
                'city' => 'Development',
                'isp' => 'Internal Network'
            ];
        }

        return Cache::remember("geoip_{$ip}", 3600, function () use ($ip) {
            try {
                // Check if it's a private IP range
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                    return [
                        'country' => 'Private',
                        'city' => 'Local Network',
                        'isp' => 'Internal'
                    ];
                }

                // Using api.iplocation.net as requested
                $response = Http::withoutVerifying()
                    ->timeout(5)
                    ->get("https://api.iplocation.net/?ip=" . $ip);

                if ($response->successful() && $response->json('response_code') === '200') {
                    return [
                        'country' => $response->json('country_name') ?? 'Unknown',
                        'city' => 'N/A', // This API only provides Country and ISP in free tier
                        'isp' => $response->json('isp') ?? 'Unknown ISP',
                    ];
                }
            } catch (\Exception $e) {
                \Log::error("GeoIP lookup failed for {$ip}: " . $e->getMessage());
            }

            return [
                'country' => 'Unknown',
                'city' => 'Unknown (' . $ip . ')',
                'isp' => 'Unknown'
            ];
        });
    }
}
