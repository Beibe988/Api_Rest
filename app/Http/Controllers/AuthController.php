<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Firebase\JWT\JWT;

class AuthController extends Controller
{
    // Registrazione utente
    public function register(Request $request)
    {
        $request->validate([
            'name'                  => 'required|string|max:255',
            'surname'               => 'required|string|max:255',   // <--- aggiunto
            'email'                 => 'required|string|email|max:255|unique:users',
            'password'              => 'required|string|min:6|confirmed',
        ]);

        DB::beginTransaction();
        try {
            $cryptedName    = Crypt::encryptString($request->name);
            $cryptedSurname = Crypt::encryptString($request->surname); // <--- aggiunto
            $cryptedEmail   = Crypt::encryptString($request->email);
            $hashEmail      = hash('sha256', strtolower(trim($request->email)));
            $hashName       = hash('sha256', strtolower(trim($request->name)));
            $hashSurname    = hash('sha256', strtolower(trim($request->surname))); // <--- aggiunto

            // 1. Crea utente in users
            $user = User::create([
                'name'       => $cryptedName,
                'surname'    => $cryptedSurname, // <--- aggiunto
                'email'      => $cryptedEmail,
                'hash_email' => $hashEmail,
                'hash_name'  => $hashName,
                'hash_surname' => $hashSurname, // <--- aggiunto
                'role'       => 'Guest',
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

            return response()->json(['message' => 'Utente registrato con successo'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Errore durante la registrazione:', [$e->getMessage()]);
            return response()->json(['error' => 'Errore durante la registrazione'], 500);
        }
    }

    // Login
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|string|email',
            'password' => 'required|string',
            'password_entry_duration' => 'nullable|integer|min:0', // <-- da frontend, opzionale
        ]);

        $hashEmail = hash('sha256', strtolower(trim($request->email)));
        $user = User::where('hash_email', $hashEmail)->first();

        if (!$user) {
            // Non aggiorno login_tests perché non conosco l'user_id
            return response()->json(['error' => 'Credenziali non valide'], 401);
        }

        // BLOCCO LOGIN dopo 3 tentativi falliti
        $test = DB::table('login_tests')->where('user_id', $user->id)->first();
        if ($test && $test->attempt_count >= 3) {
            return response()->json(['error' => 'Troppi tentativi. Riprova più tardi.'], 429);
        }

        // Recupera hash e salt
        $passRow = DB::table('user_passwords')->where('user_id', $user->id)->first();

        if (!$passRow) {
            $this->updateLoginTests($user->id, $request->input('password_entry_duration', null), true);
            return response()->json(['error' => 'Credenziali non valide'], 401);
        }

        $inputHash = hash('sha256', $request->password . $passRow->salt);

        \Log::info('LOGIN DEBUG', [
            'input_password' => $request->password,
            'db_salt'        => $passRow->salt,
            'db_hash'        => $passRow->password_hash,
            'calc_hash'      => $inputHash,
        ]);

        if ($inputHash !== $passRow->password_hash) {
            $this->updateLoginTests($user->id, $request->input('password_entry_duration', null), true);
            return response()->json(['error' => 'Credenziali non valide'], 401);
        }

        // Login riuscito: resetta tentativi e aggiorna timing
        $this->updateLoginTests($user->id, $request->input('password_entry_duration', null), false);

        // Recupera secret_jwt per utente
        $secretRow = DB::table('user_login_data')->where('user_id', $user->id)->first();
        if (!$secretRow) {
            return response()->json(['error' => 'Credenziali non valide'], 401);
        }
        $secretJwt = $secretRow->secret_jwt;

        // Prepara payload JWT
        $payload = [
            'sub'   => $user->id,
            'email' => $user->email, // già decriptata dall'accessor!
            'iat'   => time(),
            'exp'   => time() + 3600,
        ];
        $jwt = JWT::encode($payload, $secretJwt, 'HS256');

        // Salva token in user_tokens
        DB::table('user_tokens')->insert([
            'user_id'   => $user->id,
            'jwt_token' => $jwt,
            'issued_at' => now(),
        ]);

        return response()->json([
            'token' => $jwt,
            'user'  => [
                'id'      => $user->id,
                'name'    => $user->name,    // già decriptata dall'accessor!
                'surname' => $user->surname, // già decriptata dall'accessor!
                'role'    => $user->role,
            ]
        ], 200);
    }

    // Funzione di supporto: aggiorna login_tests
    private function updateLoginTests($userId, $passwordEntryDuration = null, $failed = false)
    {
        $test = DB::table('login_tests')->where('user_id', $userId)->first();
        $now = now();

        if ($test) {
            $attempt_count = $failed ? min($test->attempt_count + 1, 3) : 0; // max 3 tentativi, reset se login ok
            DB::table('login_tests')->where('user_id', $userId)->update([
                'attempt_count'            => $attempt_count,
                'password_entry_duration'  => $passwordEntryDuration ?? $test->password_entry_duration,
                'last_attempt'             => $now,
            ]);
        } else {
            DB::table('login_tests')->insert([
                'user_id'                  => $userId,
                'attempt_count'            => $failed ? 1 : 0,
                'password_entry_duration'  => $passwordEntryDuration ?? 0,
                'last_attempt'             => $now,
            ]);
        }
    }

    // Recupera utenti (admin)
    public function listUsers()
    {
        $users = User::select('id', 'name', 'surname', 'email', 'role', 'created_at')->get();

        Log::info('Utenti recuperati:', ['users' => $users]);

        return response()->json($users, 200);
    }
}
