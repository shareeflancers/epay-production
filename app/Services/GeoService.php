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

        return Cache::remember("geoip_{$ip}", 86400, function () use ($ip) {
            try {
                $response = Http::timeout(2)->get("http://ip-api.com/json/{$ip}?fields=status,country,city,isp");
                
                if ($response->successful() && $response->json('status') === 'success') {
                    return [
                        'country' => $response->json('country'),
                        'city' => $response->json('city'),
                        'isp' => $response->json('isp'),
                    ];
                }
            } catch (\Exception $e) {
                \Log::error("GeoIP lookup failed for {$ip}: " . $e->getMessage());
            }

            return [
                'country' => 'Unknown',
                'city' => 'Unknown',
                'isp' => 'Unknown'
            ];
        });
    }
}
