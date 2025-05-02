<?php

namespace App\Policies;

use App\Models\Movie;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MoviePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Movie $movie): bool
    {
        return true;
    }

    public function create(User $user): Response
    {
        return in_array($user->role, ['Admin', 'User'])
            ? Response::allow()
            : Response::deny('Non hai il permesso per creare film.');
    }

    public function update(User $user, Movie $movie): Response
    {
        return ($user->role === 'Admin' || $user->id === $movie->user_id)
            ? Response::allow()
            : Response::deny('Non hai i permessi per aggiornare questo film.');
    }

    public function delete(User $user, Movie $movie): Response
    {
        return ($user->role === 'Admin' || $user->id === $movie->user_id)
            ? Response::allow()
            : Response::deny('Non hai i permessi per eliminare questo film.');
    }

    public function restore(User $user, Movie $movie): Response
    {
        return $user->role === 'Admin'
            ? Response::allow()
            : Response::deny('Non hai i permessi per ripristinare questo film.');
    }

    public function forceDelete(User $user, Movie $movie): Response
    {
        return $user->role === 'Admin'
            ? Response::allow()
            : Response::deny('Non hai i permessi per eliminare definitivamente questo film.');
    }
}


