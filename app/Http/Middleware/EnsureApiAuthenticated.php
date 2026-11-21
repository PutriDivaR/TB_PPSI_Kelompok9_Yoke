<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->status_aktif) {
            Auth::logout();

            return response()->json(['message' => 'Silakan masuk terlebih dahulu.'], 401);
        }

        return $next($request);
    }
}
