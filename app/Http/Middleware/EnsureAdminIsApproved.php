<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminIsApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::user()?->needsAdminApproval()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Akun admin masih menunggu persetujuan super admin.']);
        }

        return $next($request);
    }
}
