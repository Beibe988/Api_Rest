<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;

class ApiPasswordResetController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                // 1. Genera un nuovo salt
                $salt = bin2hex(random_bytes(32));
                // 2. Crea l'hash con il salt
                $hash = hash('sha256', $request->password . $salt);

                // 3. Aggiorna SOLO la tabella custom
                DB::table('user_passwords')
                    ->updateOrInsert(
                        ['user_id' => $user->id],
                        [
                            'password_hash' => $hash,
                            'salt' => $salt,
                            'updated_at' => now(),
                        ]
                    );
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Password aggiornata con successo.']);
        } else {
            return response()->json(['error' => __($status)], 422);
        }
    }
}


