<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePatientPortalUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->patient_id) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized.'], 403);
            }

            return redirect()->route('portal.login');
        }

        return $next($request);
    }
}
