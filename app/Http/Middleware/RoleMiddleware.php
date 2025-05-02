<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $role)
    {
        // Controllo se l'utente è autenticato e ha il ruolo richiesto
        if (Auth::check() && Auth::user()->role === $role) {
            return $next($request);
        }

        // Se l'utente non ha il ruolo, restituisci un errore
        return response()->json(['message' => 'Accesso negato: privilegi insufficienti'], 403);
    }
}


