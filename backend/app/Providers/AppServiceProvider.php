<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Gate central pour bloquer les comptes rejetes, suspendus ou non actifs.
        Gate::define('access-active-account', fn (User $user): bool => $user->statut === 'actif');

        // Rate limiting specifique aux endpoints login/register pour limiter le brute-force.
        RateLimiter::for('auth', function (Request $request): Limit {
            $identifiant = Str::lower((string) $request->input('email', $request->ip()));

            return Limit::perMinute(5)->by($identifiant.'|'.$request->ip());
        });
    }
}
