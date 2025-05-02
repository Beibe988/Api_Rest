<?php

namespace App\Policies;

use App\Models\Episode;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EpisodePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Episode $episode): bool
    {
        return true;
    }

    public function create(User $user): Response
    {
        return in_array($user->role, ['Admin', 'User'])
            ? Response::allow()
            : Response::deny('Non hai il permesso per creare film.');
    }
    
    public function update(User $user, Episode $episode): Response
    {
        return $user->role === 'Admin' || $user->id === $episode->serie->user_id
            ? Response::allow()
            : Response::deny('Non hai il permesso per aggiornare questo episodio.');
    }

    public function delete(User $user, Episode $episode): Response
    {
        return $user->role === 'Admin' || $user->id === $episode->serie->user_id
            ? Response::allow()
            : Response::deny('Non hai il permesso per eliminare questo episodio.');
    }
}

