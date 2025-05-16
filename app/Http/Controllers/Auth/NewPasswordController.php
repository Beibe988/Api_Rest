<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Attempt password reset
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                // Recupera user_id
                $userId = $user->id;

                // Genera nuovo salt e hash per la nuova password
                $newSalt = bin2hex(random_bytes(32));
                $newHash = hash('sha256', $request->password . $newSalt);

                // Aggiorna la password nella tabella user_passwords
                DB::table('user_passwords')->where('user_id', $userId)->update([
                    'password_hash' => $newHash,
                    'salt' => $newSalt,
                    'created_at' => now(),
                ]);

                // Aggiorna la secret_jwt per invalidare i token precedenti
                $newSecretJwt = bin2hex(random_bytes(32));
                DB::table('user_login_data')->where('user_id', $userId)->update([
                    'secret_jwt' => $newSecretJwt,
                ]);

                // (Opzionale) aggiorna remember_token se lo usi
                $user->forceFill([
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        return $status == Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
    }
}

