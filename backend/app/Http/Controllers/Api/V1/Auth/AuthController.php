<?php

// Commentaire d'intention: orchestre inscription, connexion, verification et session API des utilisateurs.

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Models\CodeVerification;
use App\Models\ProfilConseiller;
use App\Models\ProfilEtudiant;
use App\Models\User;
use App\Models\ValidationConseiller;
use App\Services\Auth\VerificationCodeDeliveryService;
use App\Services\Admin\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function __construct(private readonly VerificationCodeDeliveryService $verificationDelivery)
    {
    }

    /**
     * Cree un compte etudiant ou conseiller, son profil et ses codes de verification.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $registration = DB::transaction(function () use ($validated): array {
            $user = User::query()->create([
                'name' => trim($validated['prenom'].' '.$validated['nom']),
                'email' => $validated['email'],
                // Hash explicite pour rendre la securite visible, meme si le cast User le protege aussi.
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                // Aucun compte public n'est utilisable avant la double verification.
                'statut' => 'en_attente',
                'verification_requise' => true,
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

            $codes = [
                $this->createVerificationCode($user, 'email', $user->email),
                $this->createVerificationCode($user, 'telephone', $user->telephone),
            ];

            // En cas d'echec de distribution, la transaction annule aussi le compte.
            foreach ($codes as $code) {
                $this->verificationDelivery->send($code['type'], $code['target'], $code['plain_code']);
            }

            return ['user' => $user, 'codes' => $codes];
        });

        Log::info('auth.register.success', [
            'user_id' => $registration['user']->id,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Compte cree. Verifiez votre e-mail et votre numero de telephone pour l’activer.',
            'user' => $this->publicUser($registration['user']->loadMissing(['profilEtudiant', 'profilConseiller'])),
            'verification' => $this->verificationPayload($registration['user']),
        ], 201);
    }

    /**
     * Valide un OTP. Seule la seconde verification active un etudiant et
     * delivre un jeton; le conseiller reste en attente de l'administration.
     */
    public function verifyCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'identifiant' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['email', 'telephone'])],
            'code' => ['required', 'digits:6'],
            'device_name' => ['nullable', 'string', 'max:80'],
        ]);

        $result = DB::transaction(function () use ($validated): array {
            $user = $this->userForVerification($validated['type'], $validated['identifiant']);

            if (! $user || ! $user->verification_requise) {
                return ['valid' => false];
            }

            $verification = CodeVerification::query()
                ->where('user_id', $user->id)
                ->where('type', $validated['type'])
                ->where('statut', 'en_attente')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if (! $verification || $verification->expire_le->isPast() || $verification->nb_tentatives >= 5) {
                if ($verification && $verification->statut === 'en_attente') {
                    $verification->forceFill(['statut' => 'expire'])->save();
                }

                return ['valid' => false];
            }

            if (! Hash::check($validated['code'], $verification->code)) {
                $attempts = $verification->nb_tentatives + 1;
                $verification->forceFill([
                    'nb_tentatives' => $attempts,
                    'statut' => $attempts >= 5 ? 'expire' : 'en_attente',
                ])->save();

                return ['valid' => false];
            }

            $verification->forceFill(['statut' => 'utilise'])->save();
            $verifiedColumn = $validated['type'] === 'email' ? 'email_verified_at' : 'telephone_verified_at';
            $user->forceFill([$verifiedColumn => now()])->save();
            $user->refresh();

            if ($user->email_verified_at !== null && $user->telephone_verified_at !== null) {
                $changes = ['verification_requise' => false];

                if ($user->isEtudiant()) {
                    $changes['statut'] = 'actif';
                }

                $user->forceFill($changes)->save();
            }

            return ['valid' => true, 'user' => $user->fresh()];
        });

        if (! $result['valid']) {
            throw ValidationException::withMessages([
                'code' => ['Code invalide, expire ou nombre maximal de tentatives atteint.'],
            ]);
        }

        /** @var User $user */
        $user = $result['user'];
        $response = [
            'message' => $user->statut === 'actif'
                ? 'Verification terminee. Votre compte est maintenant actif.'
                : 'Verification terminee. Votre compte conseiller attend la validation administrative.',
            'user' => $this->publicUser($user->loadMissing(['profilEtudiant', 'profilConseiller'])),
            'verification' => $this->verificationPayload($user),
        ];

        if (Gate::forUser($user)->allows('access-active-account')) {
            $response = [...$response, ...$this->tokenPayload($user, $validated['device_name'] ?? null, $request)];
        }

        return response()->json($response);
    }

    /**
     * Invalide le code precedent et envoie un nouveau code, au plus une fois
     * par minute pour un meme canal.
     */
    public function resendVerificationCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'identifiant' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['email', 'telephone'])],
        ]);

        $result = DB::transaction(function () use ($validated): array {
            $user = $this->userForVerification($validated['type'], $validated['identifiant']);

            // Reponse neutre afin de ne pas divulguer l'existence d'un compte.
            if (! $user || ! $user->verification_requise) {
                return ['sent' => true, 'code' => null];
            }

            $lastCode = CodeVerification::query()
                ->where('user_id', $user->id)
                ->where('type', $validated['type'])
                ->latest('created_at')
                ->lockForUpdate()
                ->first();

            if ($lastCode && $lastCode->created_at->greaterThan(now()->subMinute())) {
                return ['sent' => false, 'retry_after' => max(1, 60 - (int) $lastCode->created_at->diffInSeconds(now()))];
            }

            CodeVerification::query()
                ->where('user_id', $user->id)
                ->where('type', $validated['type'])
                ->where('statut', 'en_attente')
                ->update(['statut' => 'expire', 'updated_at' => now()]);

            return ['sent' => true, 'code' => $this->createVerificationCode($user, $validated['type'], $validated['identifiant'])];
        });

        if (! $result['sent']) {
            return response()->json([
                'message' => 'Veuillez attendre avant de demander un nouveau code.',
                'retry_after' => $result['retry_after'],
            ], 429);
        }

        if ($result['code']) {
            $code = $result['code'];
            $this->verificationDelivery->send($code['type'], $code['target'], $code['plain_code']);
        }

        return response()->json([
            'message' => 'Si un compte en attente correspond a cet identifiant, un code a ete envoye.',
        ]);
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
    private function createVerificationCode(User $user, string $type, string $cible): array
    {
        $plainCode = (string) random_int(100000, 999999);

        CodeVerification::query()->create([
            'user_id' => $user->id,
            'code' => Hash::make($plainCode),
            'type' => $type,
            'cible' => $cible,
            'statut' => 'en_attente',
            'expire_le' => now()->addMinutes(15),
        ]);

        return ['type' => $type, 'target' => $cible, 'plain_code' => $plainCode];
    }

    private function userForVerification(string $type, string $identifiant): ?User
    {
        $column = $type === 'email' ? 'email' : 'telephone';

        return User::query()->where($column, trim($identifiant))->first();
    }

    private function verificationPayload(User $user): array
    {
        return [
            'email_verified' => $user->email_verified_at !== null,
            'telephone_verified' => $user->telephone_verified_at !== null,
            'complete' => ! $user->verification_requise,
        ];
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
