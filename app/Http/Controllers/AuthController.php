<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Firebase\JWT\JWT;

class AuthController extends Controller
{
    // Registrazione utente
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'surname'  => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        DB::beginTransaction();
        try {
            $cryptedName    = Crypt::encryptString($request->name);
            $cryptedSurname = Crypt::encryptString($request->surname);
            $cryptedEmail   = Crypt::encryptString($request->email);

            $hashEmail      = hash('sha256', strtolower(trim($request->email)));
            $hashName       = hash('sha256', strtolower(trim($request->name)));
            $hashSurname    = hash('sha256', strtolower(trim($request->surname)));

            // 1. Crea utente in users
            $user = User::create([
                'name'         => $cryptedName,
                'surname'      => $cryptedSurname,
                'email'        => $cryptedEmail,
                'hash_email'   => $hashEmail,
                'hash_name'    => $hashName,
                'hash_surname' => $hashSurname,
                'role'         => 'Guest',
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
            DB::table('user_login_data')->insert(
                $this->filterColumns('user_login_data', [
                    'user_id'    => $user->id,
                    'secret_jwt' => $secretJwt,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );

            // 5. Inizializza User_auth
            if (DB::getSchemaBuilder()->hasTable('User_auth')) {
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

            DB::commit();
            return response()->json(['message' => 'Utente registrato con successo'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Errore durante la registrazione:', [$e->getMessage()]);
            return response()->json(['error' => 'Errore durante la registrazione'], 500);
        }
    }

    // Login "vero"
    public function login(Request $request)
    {
        $request->validate([
            'email'                    => 'required|string|email',
            'password'                 => 'required|string',
            'password_entry_duration'  => 'nullable|integer|min:0',
        ]);

        $hashEmail = hash('sha256', strtolower(trim($request->email)));
        $user = User::where('hash_email', $hashEmail)->first();
        if (!$user) {
            return response()->json(['error' => 'Credenziali non valide'], 401);
        }

        $maxAttempts = $this->getMaxLoginAttempts();

        // Lockout via User_auth (fallback login_tests)
        if (DB::getSchemaBuilder()->hasTable('User_auth')) {
            $authRow = DB::table('User_auth')->where('id_user', $user->id)->first();
            if (!$authRow) {
                DB::table('User_auth')->insert(
                    $this->filterColumns('User_auth', [
                        'id_user'         => $user->id,
                        'email_hash'      => $hashEmail,
                        'secret_jwt_token'=> null,
                        'failed_attempts' => 0,
                        'locked_at'       => null,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ])
                );
                $authRow = (object)['failed_attempts' => 0, 'locked_at' => null];
            }
            $locked = ($authRow->locked_at !== null) || ((int)$authRow->failed_attempts >= $maxAttempts);
            if ($locked) {
                return response()->json(['error' => 'Account bloccato per troppi tentativi.'], 423);
            }
        } else {
            $test = DB::table('login_tests')->where('user_id', $user->id)->first();
            if ($test && $test->attempt_count >= $maxAttempts) {
                return response()->json(['error' => 'Troppi tentativi. Riprova più tardi.'], 429);
            }
        }

        // Verifica password con salt
        $passRow = DB::table('user_passwords')->where('user_id', $user->id)->first();
        if (!$passRow) {
            $this->updateLoginTests($user->id, $request->input('password_entry_duration'), true);
            $this->updateUserAuthAttempts($user->id, $maxAttempts, true);
            return response()->json(['error' => 'Credenziali non valide'], 401);
        }

        $inputHash = hash('sha256', $request->password . $passRow->salt);

        Log::info('LOGIN DEBUG', [
            'input_password' => $request->password,
            'db_salt'        => $passRow->salt,
            'db_hash'        => $passRow->password_hash,
            'calc_hash'      => $inputHash,
        ]);

        if (!hash_equals($inputHash, (string)$passRow->password_hash)) {
            $this->updateLoginTests($user->id, $request->input('password_entry_duration'), true);
            $this->updateUserAuthAttempts($user->id, $maxAttempts, true);
            return response()->json(['error' => 'Credenziali non valide'], 401);
        }

        // Successo: reset tentativi
        $this->updateLoginTests($user->id, $request->input('password_entry_duration'), false);
        $this->updateUserAuthAttempts($user->id, $maxAttempts, false);

        // JWT + persistenze
        $jwt = $this->issueJwt($user);
        $this->persistAuthArtifacts($user->id, $jwt, $request); // user_tokens, session, fingerprint, access log

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

    // --- TEST LOGIN HASH-ONLY (hardcoded) -----------------------------------
    public function testLoginHashOnly()
    {
        if (!app()->environment('local')) {
            return response()->json(['error' => 'Not allowed in this environment'], 403);
        }

        // ======= DATI PER TEST (HASH-ONLY) =======
        $TEST_HASH_EMAIL    = 'INSERISCI_SHA256_LOWER_TRIM_EMAIL';
        $TEST_PASSWORD_HASH = 'INSERISCI_SHA256_PASSWORD_PLUS_SALT';
        $TEST_SALT          = 'INSERISCI_SALT';
        // =========================================

        $now = now();

        $user = User::where('hash_email', $TEST_HASH_EMAIL)->first();
        if (!$user) {
            return response()->json(['error' => 'Utente non trovato (hash_email)'], 404);
        }

        $maxAttempts = $this->getMaxLoginAttempts();

        // Lockout
        if (DB::getSchemaBuilder()->hasTable('User_auth')) {
            $authRow = DB::table('User_auth')->where('id_user', $user->id)->first();
            if (!$authRow) {
                DB::table('User_auth')->updateOrInsert(
                    ['id_user' => $user->id],
                    $this->filterColumns('User_auth', [
                        'email_hash'      => $TEST_HASH_EMAIL,
                        'failed_attempts' => 0,
                        'locked_at'       => null,
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ])
                );
                $authRow = DB::table('User_auth')->where('id_user', $user->id)->first();
            }
            if (($authRow->locked_at !== null) || ((int)$authRow->failed_attempts >= $maxAttempts)) {
                return response()->json(['error' => 'Account bloccato per troppi tentativi.'], 423);
            }
        }

        // Challenge: salt + hash devono combaciare
        $passRow = DB::table('user_passwords')->where('user_id', $user->id)->first();
        $match = $passRow
            && hash_equals((string)$passRow->salt, (string)$TEST_SALT)
            && hash_equals((string)$passRow->password_hash, (string)$TEST_PASSWORD_HASH);

        if (!$match) {
            $this->updateUserAuthAttempts($user->id, $maxAttempts, true);
            $this->updateLoginTests($user->id, null, true);
            return response()->json(['error' => 'Credenziali non valide'], 401);
        }

        // Successo: reset tentativi
        $this->updateUserAuthAttempts($user->id, $maxAttempts, false);
        $this->updateLoginTests($user->id, null, false);

        // JWT + persistenze
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
            'note'  => 'Login di test (hash_email + password_hash + salt). Usa il token come Bearer.',
        ], 200);
    }
    // ------------------------------------------------------------------------

    // ---------------- HELPER PRIVATI ----------------------------------------

    /** Evita errori di colonne mancanti */
    private function filterColumns(string $table, array $data): array
    {
        if (!DB::getSchemaBuilder()->hasTable($table)) return [];
        $cols = Schema::getColumnListing($table);
        return array_intersect_key($data, array_flip($cols));
    }

    /** Cap a 3: legge da Rules ma non supera mai 3 */
    private function getMaxLoginAttempts(): int
    {
        try {
            $val = (int) (DB::table('Rules')
                ->where('rule_key', 'max_login_attempts')
                ->value('rule_value') ?? 3);
        } catch (\Throwable $e) {
            $val = 3;
        }
        if ($val <= 0) $val = 3;
        return min($val, 3);
    }

    /** Aggiorna login_tests (retro-compat) */
    private function updateLoginTests($userId, $passwordEntryDuration = null, $failed = false)
    {
        $test = DB::table('login_tests')->where('user_id', $userId)->first();
        $now = now();

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

    /** Aggiorna tentativi/lock su User_auth con soglia */
    private function updateUserAuthAttempts(int $userId, int $maxAttempts, bool $failed): void
    {
        if (!DB::getSchemaBuilder()->hasTable('User_auth')) return;

        $row = DB::table('User_auth')->where('id_user', $userId)->lockForUpdate()->first();
        $now = now();

        if (!$row) {
            DB::table('User_auth')->insert([
                'id_user'         => $userId,
                'email_hash'      => DB::table('users')->where('id', $userId)->value('hash_email') ?? bin2hex(random_bytes(32)),
                'secret_jwt_token'=> null,
                'failed_attempts' => $failed ? 1 : 0,
                'locked_at'       => null,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
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
    }

    /** Genera (o crea secret e poi genera) un JWT per l'utente */
    private function issueJwt(User $user): string
    {
        $secretRow = DB::table('user_login_data')->where('user_id', $user->id)->first();
        if (!$secretRow) {
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
            $secretJwt = $secretRow->secret_jwt;
        }

        $payload = [
            'sub'   => $user->id,
            'email' => $user->email, // accessor: decriptata
            'iat'   => time(),
            'exp'   => time() + 3600,
        ];

        return JWT::encode($payload, $secretJwt, 'HS256');
    }

    /** Salva token, sessione, fingerprint e log accesso */
    private function persistAuthArtifacts(int $userId, string $jwt, Request $req): void
    {
        // user_tokens
        DB::table('user_tokens')->insert($this->filterColumns('user_tokens', [
            'user_id'   => $userId,
            'jwt_token' => $jwt,
            'issued_at' => now(),
        ]));

        // User_session (fingerprint)
        if (DB::getSchemaBuilder()->hasTable('User_session')) {
            DB::table('User_session')->insert($this->filterColumns('User_session', [
                'id_user'         => $userId,
                'token'           => hash('sha256', $jwt),
                'inizio_sessione' => now(),
                'fine_sessione'   => null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]));
        }

        // User_auth: ultimo token fingerprint
        if (DB::getSchemaBuilder()->hasTable('User_auth') && Schema::hasColumn('User_auth', 'secret_jwt_token')) {
            DB::table('User_auth')
                ->where('id_user', $userId)
                ->update($this->filterColumns('User_auth', [
                    'secret_jwt_token' => hash('sha256', $jwt),
                    'updated_at'       => now(),
                ]));
        }

        // Access log
        $this->logUserAccess($userId, $req);
    }

    /** Log su User_Access (unique: id_user+ip+user_agent) */
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

    // Recupera utenti (admin)
    public function listUsers()
    {
        $users = User::select('id', 'name', 'surname', 'email', 'role', 'created_at')->get();
        Log::info('Utenti recuperati:', ['users' => $users]);
        return response()->json($users, 200);
    }
}
