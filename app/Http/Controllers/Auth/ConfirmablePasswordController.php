<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class ConfirmablePasswordController extends Controller
{
    /**
     * Show the confirm password view.
     */
    public function show(): View
    {
        return view('auth.confirm-password');
    }

    /**
     * Confirm the user's password.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Recupera hash e salt dalla tabella user_passwords
        $passRow = DB::table('user_passwords')->where('user_id', $user->id)->first();

        if (!$passRow) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        $inputHash = hash('sha256', $request->password . $passRow->salt);

        if ($inputHash !== $passRow->password_hash) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        $request->session()->put('auth.password_confirmed_at', time());

        return redirect()->intended(route('dashboard', absolute: false));
    }
}

