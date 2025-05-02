<?php

namespace App\Policies;

use App\Models\User;
use App\Models\SerieTv;
use Illuminate\Auth\Access\Response;

class SerieTvPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['User', 'Admin']);
    }

    public function view(User $user, SerieTv $serieTv): bool
    {
        return $user->id === $serieTv->user_id || $user->role === 'Admin';
    }

    public function create(User $user): Response
    {
        return in_array($user->role, ['Admin', 'User'])
            ? Response::allow()
            : Response::deny('Non hai il permesso per creare film.');
    }

    public function update(User $user, SerieTv $serieTv): Response
    {
        return $user->id === $serieTv->user_id || $user->role === 'Admin'
            ? Response::allow()
            : Response::deny('Non hai i permessi per aggiornare questa serie.');
    }

    public function delete(User $user, SerieTv $serieTv): Response
    {
        return $user->id === $serieTv->user_id || $user->role === 'Admin'
            ? Response::allow()
            : Response::deny('Non hai i permessi per eliminare questa serie.');
    }
}
