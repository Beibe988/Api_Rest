<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Movie;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
//use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    protected $policies = [
        User::class => UserPolicy::class,   
        Movie::class => MoviePolicy::class,
    ];

    public function boot(): void
    {
        Route::middleware([
            EnsureFrontendRequestsAreStateful::class, // Sanctum Middleware
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ])->group(base_path('routes/api.php'));

        $this->registerPolicies();

    }
}
