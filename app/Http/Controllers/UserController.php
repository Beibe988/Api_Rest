<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class UserController extends Controller
{
    use AuthorizesRequests;

    // SOLO ADMIN: Visualizza tutti gli utenti
    public function index(Request $request)
    {
        $user = $request->attributes->get('user');
        $this->authorize('adminAccess', User::class);

        $users = User::all()->map(function($user) {
            return [
                'id'        => $user->id,
                'name'      => $user->name ? Crypt::decryptString($user->name) : null,
                'surname'   => $user->surname,
                'birth_year'=> $user->birth_year,
                'country'   => $user->country,
                'language'  => $user->language,
                'email'     => $user->email ? Crypt::decryptString($user->email) : null,
                'role'      => $user->role,
                'credits'   => $user->credits,
            ];
        });

        return response()->json($users, 200);
    }

    // User/Admin: Visualizza un singolo utente
    public function show(Request $request, User $user)
    {
        $this->authorize('view', $user);

        $userData = [
            'id'        => $user->id,
            'name'      => $user->name ? Crypt::decryptString($user->name) : null,
            'surname'   => $user->surname,
            'birth_year'=> $user->birth_year,
            'country'   => $user->country,
            'language'  => $user->language,
            'email'     => $user->email ? Crypt::decryptString($user->email) : null,
            'role'      => $user->role,
            'credits'   => $user->credits,
        ];

        return response()->json($userData, 200);
    }

    // User può modificare sé stesso, Admin può modificare tutti
    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'surname' => 'sometimes|string|max:255',
            'birth_year' => 'sometimes|integer',
            'country' => 'sometimes|string|max:100',
            'language' => 'sometimes|string|max:100',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'sometimes|in:User,Admin',
        ]);

        // Cripta e aggiorna hash per name/email se presenti
        if (isset($validated['name'])) {
            $validated['hash_name'] = hash('sha256', strtolower(trim($validated['name'])));
            $validated['name'] = Crypt::encryptString($validated['name']);
        }
        if (isset($validated['email'])) {
            $validated['hash_email'] = hash('sha256', strtolower(trim($validated['email'])));
            $validated['email'] = Crypt::encryptString($validated['email']);
        }

        // Gestisci la password
        if (!empty($validated['password'])) {
            $salt = bin2hex(random_bytes(32));
            $hash = hash('sha256', $validated['password'] . $salt);

            \DB::table('user_passwords')->where('user_id', $user->id)->update([
                'password_hash' => $hash,
                'salt' => $salt,
                'created_at' => now(),
            ]);

            // Aggiorna la secret_jwt per invalidare eventuali token
            $newSecretJwt = bin2hex(random_bytes(32));
            \DB::table('user_login_data')->where('user_id', $user->id)->update([
                'secret_jwt' => $newSecretJwt,
            ]);
        }
        unset($validated['password']); // NON aggiornare password in users

        $user->update($validated);

        // Risposta con dati decriptati
        $responseUser = [
            'id'        => $user->id,
            'name'      => $user->name ? Crypt::decryptString($user->name) : null,
            'surname'   => $user->surname,
            'birth_year'=> $user->birth_year,
            'country'   => $user->country,
            'language'  => $user->language,
            'email'     => $user->email ? Crypt::decryptString($user->email) : null,
            'role'      => $user->role,
            'credits'   => $user->credits,
        ];

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $responseUser
        ], 200);
    }

    // SOLO ADMIN: Crea un nuovo utente
    public function store(Request $request)
    {
        $this->authorize('adminAccess', User::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'birth_year' => 'required|integer',
            'country' => 'required|string|max:100',
            'language' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'role' => 'required|in:User,Admin',
        ]);

        \DB::beginTransaction();
        try {
            $cryptedName = Crypt::encryptString($validated['name']);
            $cryptedEmail = Crypt::encryptString($validated['email']);
            $hashEmail = hash('sha256', strtolower(trim($validated['email'])));
            $hashName = hash('sha256', strtolower(trim($validated['name'])));
            // 1. Crea utente senza campo password
            $user = User::create([
                'name'       => $cryptedName,
                'surname'    => $validated['surname'],
                'birth_year' => $validated['birth_year'],
                'country'    => $validated['country'],
                'language'   => $validated['language'],
                'email'      => $cryptedEmail,
                'hash_email' => $hashEmail,
                'hash_name'  => $hashName,
                'role'       => $validated['role'],
            ]);

            // 2. Genera salt e hash
            $salt = bin2hex(random_bytes(32));
            $hash = hash('sha256', $validated['password'] . $salt);

            // 3. Inserisci in user_passwords
            \DB::table('user_passwords')->insert([
                'user_id'       => $user->id,
                'password_hash' => $hash,
                'salt'          => $salt,
                'created_at'    => now(),
            ]);

            // 4. Crea secret JWT utente
            $secretJwt = bin2hex(random_bytes(32));
            \DB::table('user_login_data')->insert([
                'user_id'     => $user->id,
                'secret_jwt'  => $secretJwt,
                'created_at'  => now(),
            ]);

            \DB::commit();

            $responseUser = [
                'id'        => $user->id,
                'name'      => $user->name ? Crypt::decryptString($user->name) : null,
                'surname'   => $user->surname,
                'birth_year'=> $user->birth_year,
                'country'   => $user->country,
                'language'  => $user->language,
                'email'     => $user->email ? Crypt::decryptString($user->email) : null,
                'role'      => $user->role,
                'credits'   => $user->credits,
            ];

            return response()->json([
                'message' => 'User created successfully',
                'user' => $responseUser
            ], 201);

        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json([
                'message' => 'Errore durante la creazione utente.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // SOLO ADMIN: Cancella un utente
    public function destroy(Request $request, User $user)
    {
        $this->authorize('adminAccess', User::class);
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ], 200);
    }

    // User può aggiungere crediti a sé stesso
    public function addCredits(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1'
        ]);

        $user = $request->attributes->get('user');
        $user->credits += $request->amount;
        $user->save();

        return response()->json([
            'message' => 'Credits added successfully',
            'credits' => $user->credits
        ], 200);
    }
}





