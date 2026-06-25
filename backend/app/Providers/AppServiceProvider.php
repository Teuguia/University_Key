<?php

// Commentaire d'intention: enregistre les comportements applicatifs globaux au demarrage Laravel.

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
        Gate::define('access-active-account', function (User $user): bool {
            if ($user->statut !== 'actif') {
                return false;
            }

            // Les comptes historiques et les administrateurs ne sont pas concernes
            // par le nouveau parcours de double verification publique.
            if (! $user->verification_requise) {
                return true;
            }

            return $user->email_verified_at !== null && $user->telephone_verified_at !== null;
        });

        // Rate limiting specifique aux endpoints login/register pour limiter le brute-force.
        RateLimiter::for('auth', function (Request $request): Limit {
            $identifiant = Str::lower((string) $request->input('email', $request->ip()));

            return Limit::perMinute(5)->by($identifiant.'|'.$request->ip());
        });

        // Les essais OTP et les renvois sont limites par destinataire et IP.
        RateLimiter::for('verification-code', function (Request $request): Limit {
            $identifiant = Str::lower((string) $request->input('identifiant', 'unknown'));

            return Limit::perMinute(5)->by($identifiant.'|'.$request->ip());
        });

        RateLimiter::for('verification-resend', function (Request $request): Limit {
            $identifiant = Str::lower((string) $request->input('identifiant', 'unknown'));

            return Limit::perMinute(3)->by($identifiant.'|'.$request->ip());
        });
    }
}
