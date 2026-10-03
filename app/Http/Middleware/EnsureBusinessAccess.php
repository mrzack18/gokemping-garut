<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menjamin setiap request ke area admin memang dilakukan oleh admin yang
 * sudah login dan memiliki unit bisnis (BR-05).
 *
 * Query di dalam request ini otomatis ter-scope oleh BusinessScope karena
 * scope membaca business_id milik user yang sedang login.
 */
class EnsureBusinessAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->getAttribute('business_id') === null) {
            abort(403, 'Akun ini tidak tertaut ke unit bisnis mana pun.');
        }

        return $next($request);
    }
}
