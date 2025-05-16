<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        DB::beginTransaction();
        try {
            // 1. Crea l'utente (senza campo password)
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
            ]);

            // 2. Genera salt e hash
            $salt = bin2hex(random_bytes(32));
            $hash = hash('sha256', $request->password . $salt);

            // 3. Inserisci in user_passwords
            DB::table('user_passwords')->insert([
                'user_id'       => $user->id,
                'password_hash' => $hash,
                'salt'          => $salt,
                'created_at'    => now(),
            ]);

            // 4. Crea secret JWT utente
            $secretJwt = bin2hex(random_bytes(32));
            DB::table('user_login_data')->insert([
                'user_id'     => $user->id,
                'secret_jwt'  => $secretJwt,
                'created_at'  => now(),
            ]);

            DB::commit();

            event(new Registered($user));
            Auth::login($user);

            return redirect(route('dashboard', absolute: false));
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['registration' => 'Errore durante la registrazione.']);
        }
    }
}

