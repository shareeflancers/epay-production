<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuditLogMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log state-changing methods for the audit trail
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            try {
                AuditLog::create([
                    'user_id' => Auth::id(),
                    'action' => $request->path(),
                    'description' => json_encode([
                        'method' => $request->method(),
                        'params' => $request->except(['password', 'password_confirmation', 'token']),
                        'status' => $response->getStatusCode(),
                    ]),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            } catch (\Exception $e) {
                // Silently fail to not break the app if logging fails
                \Log::error('Failed to create audit log: ' . $e->getMessage());
            }
        }

        return $response;
    }
}
