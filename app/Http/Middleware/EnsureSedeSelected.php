<?php

namespace App\Http\Middleware;

use App\Support\CurrentSede;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSedeSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return $next($request);
        }

        if ($request->routeIs('sede.*', 'logout')) {
            return $next($request);
        }

        if (auth()->user()->sedes()->count() === 0) {
            return $next($request);
        }

        $sede = CurrentSede::resolveForAuthenticatedUser();

        if (! $sede) {
            return redirect()->route('sede.select');
        }

        return $next($request);
    }
}
