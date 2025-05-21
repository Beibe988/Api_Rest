<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class JwtAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        $header = $request->header('Authorization');

        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return response()->json(['error' => 'Token non fornito'], 401);
        }

        $jwt = substr($header, 7);

        // Estrai user_id dal payload JWT senza validare la firma
        $jwtParts = explode('.', $jwt);
        if (count($jwtParts) !== 3) {
            return response()->json(['error' => 'Formato token non valido'], 401);
        }
        $payload = json_decode(base64_decode(strtr($jwtParts[1], '-_', '+/')), true);

        if (!$payload || !isset($payload['sub'])) {
            return response()->json(['error' => 'Token senza user_id'], 401);
        }
        $userId = $payload['sub'];

        // Recupera la secret dal DB
        $secretRow = DB::table('user_login_data')->where('user_id', $userId)->first();
        if (!$secretRow) {
            return response()->json(['error' => 'Utente non trovato'], 401);
        }

        // Decodifica e verifica il JWT con la secret trovata
        try {
            $decoded = JWT::decode($jwt, new Key($secretRow->secret_jwt, 'HS256'));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Token non valido: ' . $e->getMessage()], 401);
        }

        // Recupera l'utente e il ruolo
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['error' => 'Utente non esistente'], 401);
        }

        \Log::info('[JWT DEBUG] Utente autenticato', [
            'id' => $user->id,
            'email' => $user->email,
            'role' => $user->role
        ]);

        // Salva l'utente (e il ruolo) nella request per i controller
        $request->attributes->set('user', $user);
        $request->attributes->set('user_role', $user->role);

        \Auth::setUser($user);

        // Se vuoi usare helper globale:
        // app()->instance('jwt_user', $user);

        return $next($request);
    }
}
