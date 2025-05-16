<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
   public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

   public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

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
