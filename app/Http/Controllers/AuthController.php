<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Firebase\JWT\JWT;
use App\Rules\ValidFiscalCode; // attiva la tua rule

class AuthController extends Controller
{
    // -------------------- REGISTER --------------------
    public function register(Request $request)
    {
        // 1) valida formati/base: niente unique su email (è cifrata), l'unicità la facciamo su hash_email
        $request->validate([
            // OBBLIGATORI (personali)
            'name'           => 'required|string|max:255',
            'surname'        => 'required|string|max:255',
            'email'          => 'required|string|email|max:255',
            'password'       => 'required|string|min:8|confirmed',
            'birth_date'     => 'required|date',
            'gender'         => ['required', Rule::in(['M','F'])],
            'birth_city'     => 'required|string|max:255',
            'birth_province' => 'required|string|max:3',
            'fiscal_code'    => [
                'required',
                'string',
                'size:16',
                new ValidFiscalCode(),                    // <-- verifica CF (checksum)
                Rule::unique('user_profiles','fiscal_code'), // <-- univoco a livello profilo
            ],
            // OPZIONALI (residenza + extra)
            'display_name'   => 'nullable|string|max:255',
            'phone'          => 'nullable|string|max:32',
            'street'         => 'nullable|string|max:255',
            'city'           => 'nullable|string|max:255',
            'province'       => 'nullable|string|max:64',
            'postal_code'    => 'nullable|string|max:16',
            'country'        => 'nullable|string|max:64',
        ]);

        // 2) calcolo hash per vincoli/ricerche
        $hashEmail   = hash('sha256', strtolower(trim($request->email)));
        $hashName    = hash('sha256', strtolower(trim($request->name)));
        $hashSurname = hash('sha256', strtolower(trim($request->surname)));

        // 3) unicità su hash_email
        $exists = DB::table('users')->where('hash_email', $hashEmail)->exists();
        if ($exists) {
            return response()->json([
                'errors' => ['email' => ['Email già registrata']]
            ], 422);
        }

        DB::beginTransaction();
        try {
            // cifratura dei campi sensibili in users
            $cryptedName    = Crypt::encryptString($request->name);
            $cryptedSurname = Crypt::encryptString($request->surname);
            $cryptedEmail   = Crypt::encryptString($request->email);

            // users
            $user = User::create([
                'name'         => $cryptedName,
                'surname'      => $cryptedSurname,
                'email'        => $cryptedEmail,
                'hash_email'   => $hashEmail,
                'hash_name'    => $hashName,
                'hash_surname' => $hashSurname,
                'role'         => 'Guest',
            ]);

            // user_passwords
            if (Schema::hasTable('user_passwords')) {
                $salt = bin2hex(random_bytes(32));
                $hash = hash('sha256', $request->password . $salt);
                DB::table('user_passwords')->insert($this->filterColumns('user_passwords', [
                    'user_id'       => $user->id,
                    'password_hash' => $hash,
                    'salt'          => $salt,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]));
            }

            // user_login_data (secret_jwt → QUI)
            if (Schema::hasTable('user_login_data')) {
                $secretJwt = bin2hex(random_bytes(32));
                DB::table('user_login_data')->updateOrInsert(
                    ['user_id' => $user->id],
                    $this->filterColumns('user_login_data', [
                        'secret_jwt' => $secretJwt,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            }

            // User_auth (solo metadati login/lock) – NIENTE secret_jwt qui
            if (Schema::hasTable('User_auth')) {
                DB::table('User_auth')->updateOrInsert(
                    ['id_user' => $user->id],
                    $this->filterColumns('User_auth', [
                        'email_hash'       => $hashEmail,
                        'secret_jwt_token' => null,
                        'failed_attempts'  => 0,
                        'locked_at'        => null,
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ])
                );
            }

            // user_profiles (dati personali e residenza opzionale)
            if (Schema::hasTable('user_profiles')) {
                DB::table('user_profiles')->updateOrInsert(
                    ['id_user' => $user->id],
                    $this->filterColumns('user_profiles', [
                        'first_name'      => $request->name,
                        'last_name'       => $request->surname,
                        'display_name'    => $request->input('display_name') ?: ($request->name.' '.$request->surname),
                        'birth_date'      => $request->birth_date,
                        'gender'          => $request->gender,
                        'birth_city'      => $request->birth_city,
                        'birth_province'  => substr(strtoupper($request->birth_province), 0, 2), // <- sigla 2
                        'fiscal_code'     => strtoupper($request->fiscal_code),
                        // residenza opzionale
                        'phone'        => $request->input('phone'),
                        'street'       => $request->input('street'),
                        'city'         => $request->input('city'),
                        'province'     => $request->input('province') ? substr(strtoupper($request->input('province')), 0, 2) : null, // <- sigla 2 se presente
                        'postal_code'  => $request->input('postal_code'),
                        'country'      => $request->input('country'),
                        'locale'       => 'it',
                        'timezone'     => 'Europe/Rome',
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ])
                );
            }

            DB::commit();
            return response()->json(['message' => 'Utente registrato con successo'], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Errore durante la registrazione', ['ex' => $e]);
            // in dev mostra il dettaglio
            if (app()->environment('local')) {
                return response()->json([
                    'error'  => 'Errore durante la registrazione',
                    'detail' => $e->getMessage(),
                    'file'   => $e->getFile(),
                    'line'   => $e->getLine(),
                ], 500);
            }
            return response()->json(['error' => 'Errore durante la registrazione'], 500);
        }
    }

    // -------------------- LOGIN --------------------
    public function login(Request $request)
    {
        $request->validate([
            'email'                   => 'required|string|email',
            'password'                => 'required|string',
            'password_entry_duration' => 'nullable|integer|min:0',
        ]);

        $hashEmail = hash('sha256', strtolower(trim($request->email)));
        $maxAttempts = $this->getMaxLoginAttempts();

        // 1) se esiste User_auth, usiamolo come fonte primaria
        $authRow = Schema::hasTable('User_auth')
            ? DB::table('User_auth')->where('email_hash', $hashEmail)->first()
            : null;

        // fallback: cerca l'utente nei users via hash_email
        if (!$authRow) {
            $user = User::where('hash_email', $hashEmail)->first();
            if (!$user) {
                return response()->json(['error' => 'Credenziali non valide'], 401);
            }
        } else {
            $user = User::find($authRow->id_user);
            if (!$user) {
                return response()->json(['error' => 'Utente mancante'], 401);
            }
            // lock/attempts se User_auth presente
            $locked = ($authRow->locked_at !== null) || ((int)$authRow->failed_attempts >= $maxAttempts);
            if ($locked) {
                return response()->json(['error' => 'Account bloccato per troppi tentativi.'], 423);
            }
        }

        // verifica password
        $passRow = DB::table('user_passwords')->where('user_id', $user->id)->first();
        if (!$passRow) {
            $this->updateUserAuthAttempts($user->id, $maxAttempts, true);
            $this->updateLoginTests($user->id, $request->input('password_entry_duration'), true);
            return response()->json(['error' => 'Credenziali non valide'], 401);
        }

        $inputHash = hash('sha256', $request->password . $passRow->salt);
        if (!hash_equals($inputHash, (string)$passRow->password_hash)) {
            $this->updateUserAuthAttempts($user->id, $maxAttempts, true);
            $this->updateLoginTests($user->id, $request->input('password_entry_duration'), true);
            return response()->json(['error' => 'Credenziali non valide'], 401);
        }

        // reset tentativi
        $this->updateUserAuthAttempts($user->id, $maxAttempts, false);
        $this->updateLoginTests($user->id, $request->input('password_entry_duration'), false);

        // firma JWT usando secret_jwt in user_login_data
        $jwt = $this->issueJwt($user);

        // persistenze (token log, sessione, fingerprint)
        $this->persistAuthArtifacts($user->id, $jwt, $request);

        return response()->json([
            'token' => $jwt,
            'user'  => [
                'id'      => $user->id,
                'name'    => $user->name,
                'surname' => $user->surname,
                'role'    => $user->role,
            ]
        ], 200);
    }

    // -------------------- TEST LOGIN (LOCAL) --------------------
    public function testLoginHashOnly()
    {
        if (!app()->environment('local')) {
            return response()->json(['error' => 'Not allowed'], 403);
        }

        $TEST_HASH_EMAIL    = 'INSERISCI_SHA256_LOWER_TRIM_EMAIL';
        $TEST_PASSWORD_HASH = 'INSERISCI_SHA256_PASSWORD_PLUS_SALT';
        $TEST_SALT          = 'INSERISCI_SALT';

        $user = User::where('hash_email', $TEST_HASH_EMAIL)->first();
        if (!$user) {
            return response()->json(['error' => 'Utente non trovato (users.hash_email)'], 404);
        }

        $maxAttempts = $this->getMaxLoginAttempts();

        if (Schema::hasTable('User_auth')) {
            $authRow = DB::table('User_auth')->where('id_user', $user->id)->first();
            if ($authRow && (($authRow->locked_at !== null) || ((int)$authRow->failed_attempts >= $maxAttempts))) {
                return response()->json(['error' => 'Account bloccato per troppi tentativi.'], 423);
            }
        }

        $passRow = DB::table('user_passwords')->where('user_id', $user->id)->first();
        $match = $passRow
            && hash_equals((string)$passRow->salt, (string)$TEST_SALT)
            && hash_equals((string)$passRow->password_hash, (string)$TEST_PASSWORD_HASH);

        if (!$match) {
            $this->updateUserAuthAttempts($user->id, $maxAttempts, true);
            $this->updateLoginTests($user->id, null, true);
            return response()->json(['error' => 'Credenziali non valide'], 401);
        }

        $this->updateUserAuthAttempts($user->id, $maxAttempts, false);
        $this->updateLoginTests($user->id, null, false);

        $jwt = $this->issueJwt($user);
        $this->persistAuthArtifacts($user->id, $jwt, request());

        return response()->json([
            'token' => $jwt,
            'user'  => [
                'id'      => $user->id,
                'name'    => $user->name,
                'surname' => $user->surname,
                'role'    => $user->role,
            ],
            'note'  => 'Login di test.',
        ], 200);
    }

    // -------------------- HELPERS --------------------
    /** Evita errori di colonne mancanti */
    private function filterColumns(string $table, array $data): array
    {
        if (!DB::getSchemaBuilder()->hasTable($table)) return [];
        $cols = Schema::getColumnListing($table);
        return array_intersect_key($data, array_flip($cols));
    }

    /** Cap a 3 tentativi */
    private function getMaxLoginAttempts(): int
    {
        try {
            $val = (int) (DB::table('Rules')->where('rule_key', 'max_login_attempts')->value('rule_value') ?? 3);
        } catch (\Throwable $e) {
            $val = 3;
        }
        if ($val <= 0) $val = 3;
        return min($val, 3);
    }

    /** Retro-compat login_tests */
    private function updateLoginTests($userId, $passwordEntryDuration = null, $failed = false): void
    {
        if (!DB::getSchemaBuilder()->hasTable('login_tests')) return;

        $test = DB::table('login_tests')->where('user_id', $userId)->first();
        $now  = now();

        if ($test) {
            $attempt_count = $failed ? min($test->attempt_count + 1, 3) : 0;
            DB::table('login_tests')->where('user_id', $userId)->update([
                'attempt_count'           => $attempt_count,
                'password_entry_duration' => $passwordEntryDuration ?? $test->password_entry_duration,
                'last_attempt'            => $now,
            ]);
        } else {
            DB::table('login_tests')->insert([
                'user_id'                 => $userId,
                'attempt_count'           => $failed ? 1 : 0,
                'password_entry_duration' => $passwordEntryDuration ?? 0,
                'last_attempt'            => $now,
            ]);
        }
    }

    /** Gestione tentativi/lock su User_auth */
    private function updateUserAuthAttempts(int $userId, int $maxAttempts, bool $failed): void
    {
        if (!DB::getSchemaBuilder()->hasTable('User_auth')) return;

        DB::transaction(function () use ($userId, $maxAttempts, $failed) {
            $row = DB::table('User_auth')->where('id_user', $userId)->lockForUpdate()->first();
            $now = now();

            if (!$row) {
                // prendi hash_email dalla tabella users
                $emailHash = DB::table('users')->where('id', $userId)->value('hash_email') ?? bin2hex(random_bytes(32));
                DB::table('User_auth')->insert($this->filterColumns('User_auth', [
                    'id_user'         => $userId,
                    'email_hash'      => $emailHash,
                    'secret_jwt_token'=> null,
                    'failed_attempts' => $failed ? 1 : 0,
                    'locked_at'       => null,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]));
                return;
            }

            if ($failed) {
                $next = min(((int)$row->failed_attempts) + 1, $maxAttempts);
                $payload = ['failed_attempts' => $next, 'updated_at' => $now];
                if ($next >= $maxAttempts) {
                    $payload['locked_at'] = $now;
                }
                DB::table('User_auth')->where('id', $row->id)->update($this->filterColumns('User_auth', $payload));
            } else {
                DB::table('User_auth')->where('id', $row->id)->update([
                    'failed_attempts' => 0,
                    'locked_at'       => null,
                    'updated_at'      => $now,
                ]);
            }
        });
    }

    /** Firma JWT con secret_jwt da user_login_data */
    private function issueJwt(User $user): string
    {
        $ttl = (int) env('JWT_TTL', 3600);
        $now = time();

        $row = DB::table('user_login_data')->where('user_id', $user->id)->first();
        if (!$row || empty($row->secret_jwt)) {
            $secretJwt = bin2hex(random_bytes(32));
            DB::table('user_login_data')->updateOrInsert(
                ['user_id' => $user->id],
                $this->filterColumns('user_login_data', [
                    'secret_jwt' => $secretJwt,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        } else {
            $secretJwt = $row->secret_jwt;
        }

        $payload = [
            'sub' => $user->id,
            'iat' => $now,
            'nbf' => $now - 5,
            'exp' => $now + $ttl,
        ];

        return JWT::encode($payload, (string) $secretJwt, 'HS256');
    }

    /** Persistenza token/log/sessione/fingerprint */
    private function persistAuthArtifacts(int $userId, string $jwt, Request $req): void
    {
        // user_tokens (log token)
        $ut = $this->filterColumns('user_tokens', [
            'user_id'   => $userId,
            'jwt_token' => $jwt,
            'issued_at' => now(),
        ]);
        if (!empty($ut)) {
            DB::table('user_tokens')->insert($ut);
        }

        // User_session (fingerprint sessione)
        if (DB::getSchemaBuilder()->hasTable('User_session')) {
            $us = $this->filterColumns('User_session', [
                'id_user'         => $userId,
                'token'           => hash('sha256', $jwt),
                'inizio_sessione' => now(),
                'fine_sessione'   => null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
            if (!empty($us)) {
                DB::table('User_session')->insert($us);
            }
        }

        // Fingerprint ultimo token su User_auth (se c'è la colonna)
        if (DB::getSchemaBuilder()->hasTable('User_auth') && Schema::hasColumn('User_auth', 'secret_jwt_token')) {
            $ua = $this->filterColumns('User_auth', [
                'secret_jwt_token' => hash('sha256', $jwt),
                'updated_at'       => now(),
            ]);
            if (!empty($ua)) {
                DB::table('User_auth')->where('id_user', $userId)->update($ua);
            }
        }

        // Access log
        $this->logUserAccess($userId, $req);
    }

    private function logUserAccess(int $userId, Request $req): void
    {
        if (!DB::getSchemaBuilder()->hasTable('User_Access')) return;

        $ip = (string) $req->ip();
        $ua = substr((string) $req->userAgent(), 0, 255);

        $row = DB::table('User_Access')->where([
            'id_user'    => $userId,
            'ip'         => $ip,
            'user_agent' => $ua,
        ])->first();

        if ($row) {
            DB::table('User_Access')->where('id', $row->id)->update([
                'last_seen_at' => now(),
                'hits'         => DB::raw('hits + 1'),
                'updated_at'   => now(),
            ]);
        } else {
            DB::table('User_Access')->insert([
                'id_user'      => $userId,
                'ip'           => $ip,
                'user_agent'   => $ua,
                'last_seen_at' => now(),
                'hits'         => 1,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
    }
}

