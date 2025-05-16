<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\DB;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user = $request->user();

        // Recupera hash e salt attuali
        $passRow = DB::table('user_passwords')->where('user_id', $user->id)->first();

        if (!$passRow) {
            return back()->withErrors([
                'current_password' => 'Errore interno: password non trovata.',
            ]);
        }

        // Verifica la password attuale
        $inputHash = hash('sha256', $validated['current_password'] . $passRow->salt);
        if ($inputHash !== $passRow->password_hash) {
            return back()->withErrors([
                'current_password' => 'La password attuale non è corretta.',
            ]);
        }

        // Genera nuovo salt e hash per la nuova password
        $newSalt = bin2hex(random_bytes(32));
        $newHash = hash('sha256', $validated['password'] . $newSalt);

        // Aggiorna la password
        DB::table('user_passwords')->where('user_id', $user->id)->update([
            'password_hash' => $newHash,
            'salt' => $newSalt,
            'created_at' => now(),
        ]);

        // (Consigliato) Aggiorna la secret_jwt per invalidare tutti i vecchi JWT
        $newSecretJwt = bin2hex(random_bytes(32));
        DB::table('user_login_data')->where('user_id', $user->id)->update([
            'secret_jwt' => $newSecretJwt,
        ]);

        return back()->with('status', 'password-updated');
    }
}

