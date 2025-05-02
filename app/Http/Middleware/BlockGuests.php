<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockGuests
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && auth()->user()->role === 'Guest') {
            return response()->json(['message' => 'Accesso negato. Solo utenti autorizzati.'], 403);
        }

        return $next($request);
    }
}
