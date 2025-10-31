<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureGoogleConnected
{
    public function handle(Request $request, Closure $next)
    {
        $tokenPath = storage_path('app/google/token.json');
        if (!file_exists($tokenPath)) {
            return redirect()->route('google.auth');
        }
        return $next($request);
    }
}
