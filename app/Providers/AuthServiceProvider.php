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
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Le policy dell'applicazione.
     */
    
    protected $policies = [
        Movie::class => MoviePolicy::class,
        SerieTv::class => SerieTvPolicy::class,
        User::class => UserPolicy::class,
        Episode::class => EpisodePolicy::class,
    ];

    /**
     * Bootstrap dei servizi di autenticazione/autorizzazione.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Definizione del gate per gli amministratori
        Gate::define('adminAccess', function (User $user) {
            return $user->role === 'Admin'; 
        });
        
        // Definizione del gate per gli ospiti
        Gate::define('blockGuests', function (User $user) {
            return $user->role !== 'Guest';
        });
        
    }
}
