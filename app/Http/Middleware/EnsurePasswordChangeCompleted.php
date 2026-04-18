<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChangeCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! (bool) $user->must_change_password) {
            return $next($request);
        }

        if (
            $request->is('api/profile') ||
            $request->is('api/profile/password/force-change') ||
            $request->is('api/logout')
        ) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Password change required',
            'code' => 'password_change_required',
        ], 423);
    }
}

