<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        // Decifra i dati per passarli alla view
        $user = $request->user();

        $user->name = Crypt::decryptString($user->name);
        $user->surname = Crypt::decryptString($user->surname);
        $user->email = Crypt::decryptString($user->email);

        return view('profile.edit', [
            'user' => $user,
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // Cripta i dati sensibili PRIMA di salvare
        if (isset($validated['name'])) {
            $user->name = Crypt::encryptString($validated['name']);
            $user->hash_name = hash('sha256', strtolower(trim($validated['name'])));
        }

        if (isset($validated['surname'])) {
            $user->surname = Crypt::encryptString($validated['surname']);
            $user->hash_surname = hash('sha256', strtolower(trim($validated['surname'])));
        }

        if (isset($validated['email'])) {
            $user->email = Crypt::encryptString($validated['email']);
            $user->hash_email = hash('sha256', strtolower(trim($validated['email'])));
            $user->email_verified_at = null;
        }

        // Altri campi normali
        foreach (['birth_year', 'country', 'language'] as $field) {
            if (isset($validated[$field])) {
                $user->$field = $validated[$field];
            }
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required'],
        ]);

        $user = $request->user();

        // Verifica password con salt e hash
        $passRow = \DB::table('user_passwords')->where('user_id', $user->id)->first();

        if (!$passRow) {
            return back()->withErrors([
                'password' => 'Errore interno: password non trovata.',
            ], 'userDeletion');
        }

        $inputHash = hash('sha256', $request->password . $passRow->salt);

        if ($inputHash !== $passRow->password_hash) {
            return back()->withErrors([
                'password' => 'La password inserita non è corretta.',
            ], 'userDeletion');
        }

        // Logout ed elimina utente (con cascade su tabelle collegate)
        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}

