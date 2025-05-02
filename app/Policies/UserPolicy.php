<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Permette a qualsiasi utente autenticato di visualizzare il proprio profilo.
     */
    public function view(User $user, User $targetUser): Response
    {
        return $user->id === $targetUser->id || $user->role === 'Admin'
            ? Response::allow()
            : Response::deny('Non puoi visualizzare questi dati.');
    }

    /**
     * Permette agli utenti di modificare il proprio profilo e agli admin di modificare qualsiasi utente.
     */
    public function update(User $user, User $targetUser): Response
    {
        return $user->id === $targetUser->id || $user->role === 'Admin'
            ? Response::allow()
            : Response::deny('Non puoi modificare questi dati.');
    }

    /**
     * Permette solo agli amministratori di visualizzare la lista degli utenti.
     */
    public function viewAll(User $user): Response
    {
        return $user->role === 'Admin'
            ? Response::allow()
            : Response::deny('Accesso negato.');
    }

    /**
     * Permette solo agli amministratori di creare nuovi utenti.
     */
    public function create(User $user): Response
    {
        return $user->role === 'Admin'
            ? Response::allow()
            : Response::deny('Non puoi creare nuovi utenti.');
    }

    /**
     * Permette solo agli amministratori di eliminare utenti.
     */
    public function delete(User $user, User $targetUser): Response
    {
        return $user->role === 'Admin'
            ? Response::allow()
            : Response::deny('Non puoi eliminare questo utente.');
    }

    /**
     * Permette solo agli amministratori di accedere a funzionalità riservate.
     */
    public function adminAccess(User $user): Response
    {
        return $user->role === 'Admin'
            ? Response::allow()
            : Response::deny('Accesso negato.');
    }
}



