<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiRole
{
    /** @param list<string> $roles */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        abort_unless($user, 401, 'Silakan masuk terlebih dahulu.');

        $role = mb_strtolower(trim((string) $user->peran?->nama_peran));
        $allowed = array_map(fn (string $value): string => mb_strtolower(trim($value)), $roles);
        abort_unless(in_array($role, $allowed, true), 403, 'Akses tidak sesuai peran akun.');

        return $next($request);
    }
}
