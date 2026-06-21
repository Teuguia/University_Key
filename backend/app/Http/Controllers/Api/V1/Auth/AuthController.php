<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Models\CodeVerification;
use App\Models\ProfilConseiller;
use App\Models\ProfilEtudiant;
use App\Models\User;
use App\Models\ValidationConseiller;
use App\Services\Admin\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Cree un compte etudiant ou conseiller, son profil et ses codes de verification.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $payload = DB::transaction(function () use ($validated, $request): array {
            $user = User::query()->create([
                'name' => trim($validated['prenom'].' '.$validated['nom']),
                'email' => $validated['email'],
                // Hash explicite pour rendre la securite visible, meme si le cast User le protege aussi.
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                // Les conseillers attendent la validation admin avant d'acceder a leur dashboard.
                'statut' => $validated['role'] === 'conseiller' ? 'en_attente' : 'actif',
                'telephone' => $validated['telephone'],
                'langue_preferee' => $validated['langue_preferee'] ?? 'fr',
            ]);

            // Chaque role cree le profil correspondant aux tables de la base.
            if ($validated['role'] === 'conseiller') {
                ProfilConseiller::query()->create([
                    'user_id' => $user->id,
                    'prenom' => $validated['prenom'],
                    'nom' => $validated['nom'],
                    'specialite' => $validated['specialite'],
                ]);

                // Demande minimale visible dans l'espace admin, a completer ensuite par documents.
                ValidationConseiller::query()->create([
                    'conseiller_id' => $user->id,
                    'statut' => 'en_attente',
                    'diplome_principal' => 'A completer',
                    'etablissement_diplome' => 'A completer',
                    'annees_experience' => 0,
                    'description_experience' => 'A completer',
                    'specialite' => $validated['specialite'],
                ]);
            } else {
                ProfilEtudiant::query()->create([
                    'user_id' => $user->id,
                    'prenom' => $validated['prenom'],
                    'nom' => $validated['nom'],
                ]);
            }

            $this->createVerificationCode($user, 'email', $user->email);
            $this->createVerificationCode($user, 'telephone', $user->telephone);

            return $this->tokenPayload($user, $request->input('device_name'), $request);
        });

        Log::info('auth.register.success', [
            'user_id' => $payload['user']['id'],
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => $payload['user']['role'] === 'conseiller'
                ? 'Compte conseiller cree. Il sera accessible apres validation par un administrateur.'
                : 'Compte cree avec succes. Verification email et telephone requise.',
            ...$payload,
        ], 201);
    }

    /**
     * Connecte un utilisateur par email ou telephone et retourne un token Sanctum.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $identifiant = $validated['email'];

        $user = User::query()
            ->where('email', $identifiant)
            ->orWhere('telephone', $identifiant)
            // Permet la connexion du compte admin par son nom public, par exemple MINESEC.
            ->orWhere('name', $identifiant)
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            Log::warning('auth.login.failed', [
                'identifiant' => $identifiant,
                'ip' => $request->ip(),
            ]);

            throw ValidationException::withMessages([
                'email' => ['Identifiants invalides.'],
            ]);
        }

        if (Gate::forUser($user)->denies('access-active-account')) {
            Log::warning('auth.login.blocked_status', [
                'user_id' => $user->id,
                'statut' => $user->statut,
                'ip' => $request->ip(),
            ]);

            throw ValidationException::withMessages([
                'email' => ['Ce compte ne peut pas se connecter actuellement.'],
            ]);
        }

        $user->forceFill(['derniere_connexion' => now()])->save();

        Log::info('auth.login.success', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
        ]);

        if ($user->isAdmin()) {
            app(ActivityLogService::class)->record(
                $user,
                'Connexion administrateur',
                "{$user->name} s’est connecte a l’espace administrateur.",
                $request,
                ['target_type' => 'session', 'target_id' => $user->id]
            );
        }

        return response()->json([
            'message' => 'Connexion reussie.',
            ...$this->tokenPayload($user, $request->input('device_name'), $request),
        ]);
    }

    /**
     * Retourne les informations du compte authentifie.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing(['profilEtudiant', 'profilConseiller']);

        return response()->json([
            'data' => $this->publicUser($user),
        ]);
    }

    /**
     * Supprime uniquement le token courant pour deconnecter l'appareil actif.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        Log::info('auth.logout.success', [
            'user_id' => $request->user()->id,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Deconnexion reussie.',
        ]);
    }

    /**
     * Cree un code OTP a 6 chiffres pour email ou telephone.
     */
    private function createVerificationCode(User $user, string $type, string $cible): void
    {
        CodeVerification::query()->create([
            'user_id' => $user->id,
            'code' => (string) random_int(100000, 999999),
            'type' => $type,
            'cible' => $cible,
            'statut' => 'en_attente',
            'expire_le' => now()->addMinutes(15),
        ]);
    }

    /**
     * Cree le token Sanctum et retourne le format commun des reponses auth.
     */
    private function tokenPayload(User $user, ?string $deviceName, Request $request): array
    {
        $tokenName = $deviceName ?: 'web-'.($request->userAgent() ? substr(sha1($request->userAgent()), 0, 10) : 'api');
        $token = $user->createToken($tokenName, ["role:{$user->role}"], now()->addDays(30))->plainTextToken;

        return [
            'token_type' => 'Bearer',
            'token' => $token,
            'user' => $this->publicUser($user->loadMissing(['profilEtudiant', 'profilConseiller'])),
        ];
    }

    /**
     * Expose uniquement les donnees utilisateur utiles au frontend.
     */
    private function publicUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'telephone' => $user->telephone,
            'role' => $user->role,
            'statut' => $user->statut,
            'langue_preferee' => $user->langue_preferee,
            'derniere_connexion' => $user->derniere_connexion,
            'profil_etudiant' => $user->profilEtudiant,
            'profil_conseiller' => $user->profilConseiller,
        ];
    }
}
