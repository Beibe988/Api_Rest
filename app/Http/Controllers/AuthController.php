<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // Registrazione utente
    public function register(Request $request)
    {
        $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|string|email|max:255|unique:users',
            'password'              => 'required|string|min:6|confirmed',
        ]);
        
        \Log::info('Dati ricevuti per registrazione:', $request->all());

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'Guest',
        ]);

        return response()->json(['message' => 'Utente registrato con successo'], 201);
    }

    // Login
    public function login(Request $request)
{
    $request->validate([
        'email'    => 'required|string|email',
        'password' => 'required|string',
    ]);

    Log::info('Tentativo di login', ['email' => $request->email]);

    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        Log::warning('Credenziali non valide', ['email' => $request->email]);
        return response()->json(['error' => 'Credenziali non valide'], 401);
    }

    $token = $user->createToken('authToken')->plainTextToken;

    Log::info('Login riuscito', ['user_id' => $user->id]);
    Log::info('Token generato con successo', ['token' => $token]);


    return response()->json([
        'token' => $token,
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'role' => $user->role,
        ]
    ], 200);
}


    // Recupera utenti (admin)
    public function listUsers()
    {
        $users = User::select('id', 'name', 'email', 'role', 'created_at')->get();

        Log::info('Utenti recuperati:', ['users' => $users]);

        return response()->json($users, 200);
    }
}

