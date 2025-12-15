<?php

namespace App\Providers;

use App\Models\Movie;
use App\Models\SerieTv;
use App\Models\Episode;
use App\Models\User;
use App\Policies\MoviePolicy;
use App\Policies\SerieTvPolicy;
use App\Policies\EpisodePolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Mappa delle Policy dell’app.
     */
    protected $policies = [
        Movie::class   => MoviePolicy::class,
        SerieTv::class => SerieTvPolicy::class,
        Episode::class => EpisodePolicy::class,
        User::class    => UserPolicy::class,
    ];

    /**
     * Bootstrap dei servizi di authz.
     */
    public function boot(): void
    {
        // Registra le policy mappate sopra
        $this->registerPolicies();

        // Gate usati in routes/api.php
        Gate::define('adminAccess', fn (User $user) => $user->role === 'Admin');
        Gate::define('blockGuests', fn (User $user) => $user->role !== 'Guest');

        // --- Fallback generici (NON interferiscono con le Policy specifiche) ---
        // Se esiste una Policy per il Model, Laravel usa quella; altrimenti cade qui.
        Gate::define('create', fn (User $user, mixed $target = null) =>
            in_array($user->role, ['User', 'Admin'], true)
        );
        Gate::define('update', fn (User $user, mixed $target = null) =>
            in_array($user->role, ['User', 'Admin'], true)
        );
        Gate::define('delete', fn (User $user, mixed $target = null) =>
            $user->role === 'Admin'
        );
    }
}

