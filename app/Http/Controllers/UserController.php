<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    use AuthorizesRequests;

    /**
     * SOLO ADMIN: lista utenti
     */
    public function index(Request $request)
    {
        // Gate senza modello (definito in AuthServiceProvider)
        $this->authorize('adminAccess');

        $users = User::query()
            ->select([
                'id','name','surname','email','role','credits','created_at',
                'birth_year','country','language'
            ])
            ->orderBy('id', 'asc')
            ->get()
            ->map(function (User $u) {
                // Se il Model ha accessor/mutator per cifrare/decifrare,
                // qui i valori sono già in chiaro.
                return [
                    'id'         => $u->id,
                    'name'       => $u->name,
                    'surname'    => $u->surname,
                    'email'      => $u->email,
                    'role'       => $u->role,
                    'credits'    => $u->credits,
                    'created_at' => $u->created_at,
                    'birth_year' => $u->birth_year,
                    'country'    => $u->country,
                    'language'   => $u->language,
                ];
            });

        return response()->json($users, 200);
    }

    /**
     * User/Admin: dettaglio
     */
    public function show(Request $request, User $user)
    {
        $this->authorize('view', $user);

        return response()->json([
            'id'         => $user->id,
            'name'       => $user->name,
            'surname'    => $user->surname,
            'email'      => $user->email,
            'role'       => $user->role,
            'credits'    => $user->credits,
            'created_at' => $user->created_at,
            'birth_year' => $user->birth_year,
            'country'    => $user->country,
            'language'   => $user->language,
        ], 200);
    }

    /**
     * User può modificare sé stesso, Admin chiunque
     */
    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $validated = $request->validate([
            'name'       => 'sometimes|string|max:255',
            'surname'    => 'sometimes|string|max:255',
            'birth_year' => 'sometimes|integer',
            'country'    => 'sometimes|string|max:100',
            'language'   => 'sometimes|string|max:100',
            'email'      => 'sometimes|email',
            'password'   => 'nullable|string|min:8|confirmed',
            'role'       => 'sometimes|in:User,Admin',
        ]);

        // Email: unicità tramite hash_email (dato che email è cifrata)
        if (isset($validated['email'])) {
            $newEmail   = trim(strtolower($validated['email']));
            $hashEmail  = hash('sha256', $newEmail);

            $exists = User::where('hash_email', $hashEmail)
                ->where('id', '<>', $user->id)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'email' => ['Email già presente.'],
                ]);
            }

            $user->hash_email = $hashEmail;
            $user->email      = $validated['email']; // il mutator cifra
        }

        // Nome / Cognome: aggiorna hash_* e lascia al mutator la cifratura
        if (isset($validated['name'])) {
            $user->hash_name = hash('sha256', strtolower(trim($validated['name'])));
            $user->name      = $validated['name'];
        }

        if (isset($validated['surname'])) {
            $user->hash_surname = hash('sha256', strtolower(trim($validated['surname'])));
            $user->surname      = $validated['surname'];
        }

        // Altri campi base
        foreach (['birth_year','country','language','role'] as $k) {
            if (array_key_exists($k, $validated)) {
                $user->{$k} = $validated[$k];
            }
        }

        // Password: aggiorna tabella password e invalida secret JWT
        if (!empty($validated['password'])) {
            $salt = bin2hex(random_bytes(32));
            $hash = hash('sha256', $validated['password'] . $salt);

            DB::table('user_passwords')->updateOrInsert(
                ['user_id' => $user->id],
                [
                    'password_hash' => $hash,
                    'salt'          => $salt,
                    'updated_at'    => now(),
                    // Se non esiste la riga, valorizza anche created_at
                    'created_at'    => now(),
                ]
            );

            // Invalida i token correnti ruotando la secret
            DB::table('user_login_data')->updateOrInsert(
                ['user_id' => $user->id],
                ['secret_jwt' => bin2hex(random_bytes(32)), 'updated_at' => now(), 'created_at' => now()]
            );
        }

        $user->save();

        return response()->json([
            'message' => 'User updated successfully',
            'user'    => [
                'id'         => $user->id,
                'name'       => $user->name,
                'surname'    => $user->surname,
                'email'      => $user->email,
                'role'       => $user->role,
                'credits'    => $user->credits,
                'created_at' => $user->created_at,
                'birth_year' => $user->birth_year,
                'country'    => $user->country,
                'language'   => $user->language,
            ],
        ], 200);
    }

    /**
     * SOLO ADMIN: Crea un nuovo utente
     */
    public function store(Request $request)
    {
        $this->authorize('adminAccess');

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'surname'    => 'required|string|max:255',
            'birth_year' => 'nullable|integer',
            'country'    => 'nullable|string|max:100',
            'language'   => 'nullable|string|max:100',
            'email'      => 'required|email',
            'password'   => 'required|min:8|confirmed',
            'role'       => 'required|in:User,Admin',
        ]);

        $hashEmail = hash('sha256', strtolower(trim($validated['email'])));
        if (User::where('hash_email', $hashEmail)->exists()) {
            throw ValidationException::withMessages([
                'email' => ['Email già presente.'],
            ]);
        }

        DB::beginTransaction();
        try {
            $user = new User();
            $user->name         = $validated['name'];     // mutator cifra
            $user->surname      = $validated['surname'];  // mutator cifra (se previsto)
            $user->email        = $validated['email'];    // mutator cifra
            $user->hash_name    = hash('sha256', strtolower(trim($validated['name'])));
            $user->hash_surname = hash('sha256', strtolower(trim($validated['surname'])));
            $user->hash_email   = $hashEmail;
            $user->birth_year   = $validated['birth_year'] ?? null;
            $user->country      = $validated['country'] ?? null;
            $user->language     = $validated['language'] ?? null;
            $user->role         = $validated['role'];
            $user->save();

            $salt = bin2hex(random_bytes(32));
            $hash = hash('sha256', $validated['password'] . $salt);

            DB::table('user_passwords')->insert([
                'user_id'       => $user->id,
                'password_hash' => $hash,
                'salt'          => $salt,
                'created_at'    => now(),
            ]);

            DB::table('user_login_data')->insert([
                'user_id'    => $user->id,
                'secret_jwt' => bin2hex(random_bytes(32)),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'User created successfully',
                'user'    => [
                    'id'         => $user->id,
                    'name'       => $user->name,
                    'surname'    => $user->surname,
                    'email'      => $user->email,
                    'role'       => $user->role,
                    'credits'    => $user->credits,
                    'created_at' => $user->created_at,
                    'birth_year' => $user->birth_year,
                    'country'    => $user->country,
                    'language'   => $user->language,
                ],
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Errore durante la creazione utente.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * SOLO ADMIN: delete
     */
    public function destroy(Request $request, User $user)
    {
        $this->authorize('adminAccess');

        $user->delete();

        return response()->json(['message' => 'User deleted successfully'], 200);
    }

    /**
     * User: aggiunge crediti a sé stesso
     */
    public function addCredits(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1'
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user(); // impostato da JwtAuthenticate (Auth::setUser)
        if (!$user) {
            return response()->json(['error' => 'Non autenticato'], 401);
        }

        $user->credits = (int) $user->credits + (int) $request->input('amount', 0);
        $user->save();

        return response()->json([
            'message' => 'Credits added successfully',
            'credits' => $user->credits,
        ], 200);
    }
}








