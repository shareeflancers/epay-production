<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ApiLog;
use Symfony\Component\HttpFoundation\Response;

class ApiLogMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $response = $next($request);
        $duration = round((microtime(true) - $startTime) * 1000);

        try {
            ApiLog::create([
                'endpoint' => $request->fullUrl(),
                'method' => $request->method(),
                'request_payload' => $request->except(['password', 'password_confirmation']),
                'response_payload' => json_decode($response->getContent(), true),
                'status_code' => $response->getStatusCode(),
                'ip_address' => $request->ip(),
                'country' => null,
                'city' => null,
                'isp' => null,
                'duration_ms' => $duration,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to create API log: ' . $e->getMessage());
        }

        return $response;
    }
}
