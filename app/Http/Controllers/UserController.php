<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class UserController extends Controller
{
    use AuthorizesRequests;

    // SOLO ADMIN: Visualizza tutti gli utenti
    public function index()
    {
        $this->authorize('adminAccess', User::class);
        return response()->json(User::all(), 200);
    }

    // User/Admin: Visualizza un singolo utente
    public function show(User $user)
    {
        $this->authorize('view', $user);
        return response()->json($user, 200);
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

        // Se la password è vuota, rimuoverla dal payload
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user
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

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user
        ], 201);
    }

    // SOLO ADMIN: Cancella un utente
    public function destroy(User $user)
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

        $user = auth()->user();
        $user->credits += $request->amount;
        $user->save();

        return response()->json([
            'message' => 'Credits added successfully',
            'credits' => $user->credits
        ], 200);
    }
}



