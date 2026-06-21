<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class StudentDashboardController extends Controller
{
    /**
     * Retourne toutes les donnees necessaires au tableau de bord etudiant.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing('profilEtudiant');

        if (! $user->isEtudiant()) {
            return response()->json([
                'message' => 'Ce tableau de bord est reserve aux etudiants.',
            ], 403);
        }

        $profileCompletion = $this->profileCompletion($user->profilEtudiant);
        $orientationScore = $this->orientationScore($user->id);
        $combinedCompatibility = $this->combinedCompatibility($profileCompletion['percentage'], $orientationScore);

        return response()->json([
            'data' => [
                'student' => $this->student($user),
                'profile_completion' => $profileCompletion,
                'compatibility' => [
                    'score' => $combinedCompatibility,
                    'profile_weight' => 40,
                    'orientation_weight' => 60,
                    'profile_score' => $profileCompletion['percentage'],
                    'orientation_score' => $orientationScore,
                    'has_orientation_score' => $orientationScore !== null,
                ],
                'metrics' => [
                    'tests_completed' => $this->completedTestsCount($user->id),
                    'max_compatibility' => $combinedCompatibility,
                    'favorite_schools' => $this->favoriteSchoolsCount($user->id),
                    'open_conversations' => $this->openConversationsCount($user->id),
                    'unread_messages' => $this->unreadMessagesCount($user->id),
                ],
                'recent_tests' => $this->recentTests($user->id),
                'recommended_programs' => $this->recommendedPrograms($user->id),
                'recommended_schools' => $this->recommendedSchools($user->id),
                'recent_messages' => $this->recentMessages($user->id),
                'reminders' => $this->reminders($user->id),
                'domains' => $this->domains(),
            ],
        ]);
    }

    /**
     * Met a jour la photo de profil etudiant depuis la camera ou la galerie.
     */
    public function updatePhoto(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing('profilEtudiant');

        if (! $user->isEtudiant()) {
            return response()->json([
                'message' => 'La photo de profil etudiant est reservee aux etudiants.',
            ], 403);
        }

        $validated = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $profile = $user->profilEtudiant ?: $user->profilEtudiant()->create([
            'prenom' => str($user->name)->before(' ')->toString(),
            'nom' => str($user->name)->after(' ')->toString() ?: $user->name,
        ]);

        if ($profile->photo && ! str($profile->photo)->startsWith(['http://', 'https://', '/'])) {
            Storage::disk('public')->delete($profile->photo);
        }

        $profile->forceFill([
            'photo' => $validated['photo']->store('student-profile-photos', 'public'),
        ])->save();

        $user->setRelation('profilEtudiant', $profile->refresh());
        $profileCompletion = $this->profileCompletion($profile);
        $orientationScore = $this->orientationScore($user->id);
        $combinedCompatibility = $this->combinedCompatibility($profileCompletion['percentage'], $orientationScore);

        return response()->json([
            'message' => 'Photo de profil mise a jour.',
            'data' => [
                'student' => $this->student($user),
                'profile_completion' => $profileCompletion,
                'compatibility' => [
                    'score' => $combinedCompatibility,
                    'profile_weight' => 40,
                    'orientation_weight' => 60,
                    'profile_score' => $profileCompletion['percentage'],
                    'orientation_score' => $orientationScore,
                    'has_orientation_score' => $orientationScore !== null,
                ],
            ],
        ]);
    }

    /**
     * Formate l'identite de l'etudiant pour le front.
     */
    private function student($user): array
    {
        $profile = $user->profilEtudiant;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'first_name' => $profile?->prenom ?: str($user->name)->before(' ')->toString(),
            'email' => $user->email,
            'telephone' => $user->telephone,
            'photo_url' => $this->publicPhotoUrl($profile?->photo),
            'city' => $profile?->ville,
            'region' => $profile?->region,
            'objective' => $profile?->objectif_professionnel,
            'last_login' => $user->derniere_connexion,
        ];
    }

    /**
     * Calcule une progression simple du profil selon les champs disponibles.
     */
    private function profileCompletion($profile): array
    {
        if (! $profile) {
            return ['percentage' => 0, 'missing_fields' => ['profil']];
        }

        $fields = [
            'date_naissance',
            'sexe',
            'ville',
            'region',
            'type_bac',
            'annee_bac',
            'moyenne_generale',
            'centres_interet',
            'objectif_professionnel',
            'photo',
        ];

        $filled = collect($fields)->filter(fn (string $field): bool => filled($profile->{$field}));
        $missing = collect($fields)->reject(fn (string $field): bool => filled($profile->{$field}))->values();

        return [
            'percentage' => (int) round(($filled->count() / count($fields)) * 100),
            'missing_fields' => $missing->all(),
        ];
    }

    /**
     * Recupere le meilleur score issu des recommandations ou des tests termines.
     */
    private function orientationScore(int $userId): ?int
    {
        if (! $this->tableHasColumns('recommandations', ['user_id', 'score'])) {
            return $this->lastTestScore($userId);
        }

        $recommendationScore = DB::table('recommandations')
            ->where('user_id', $userId)
            ->max('score');

        if ($recommendationScore !== null) {
            return (int) round((float) $recommendationScore);
        }

        return $this->lastTestScore($userId);
    }

    /**
     * Retourne le meilleur score de test si les colonnes existent.
     */
    private function lastTestScore(int $userId): ?int
    {
        if (! $this->tableHasColumns('sessions_test', ['user_id', 'statut', 'score_global'])) {
            return null;
        }

        $testScore = DB::table('sessions_test')
            ->where('user_id', $userId)
            ->where('statut', 'termine')
            ->max('score_global');

        return $testScore !== null ? (int) round((float) $testScore) : null;
    }

    /**
     * Combine le profil et le test: 40% profil, 60% orientation.
     */
    private function combinedCompatibility(int $profileScore, ?int $orientationScore): int
    {
        return (int) round(($profileScore * 0.4) + (($orientationScore ?? 0) * 0.6));
    }

    /**
     * Retourne les derniers tests termines par l'etudiant.
     */
    private function recentTests(int $userId): array
    {
        if (! $this->tableHasColumns('sessions_test', ['user_id', 'statut', 'score_global', 'termine_le', 'updated_at'])) {
            return [];
        }

        $query = DB::table('sessions_test');

        if ($this->tableHasColumns('tests_orientation', ['id', 'titre']) && Schema::hasColumn('sessions_test', 'test_orientation_id')) {
            $query->leftJoin('tests_orientation', 'tests_orientation.id', '=', 'sessions_test.test_orientation_id');
            $columns = [
                'sessions_test.id',
                'tests_orientation.titre',
                'sessions_test.score_global',
                'sessions_test.termine_le',
                'sessions_test.updated_at',
            ];
        } else {
            $columns = [
                'sessions_test.id',
                DB::raw("'Test d''orientation' as titre"),
                'sessions_test.score_global',
                'sessions_test.termine_le',
                'sessions_test.updated_at',
            ];
        }

        return $query
            ->where('sessions_test.user_id', $userId)
            ->where('sessions_test.statut', 'termine')
            ->orderByDesc('sessions_test.termine_le')
            ->orderByDesc('sessions_test.updated_at')
            ->limit(3)
            ->get($columns)
            ->map(fn ($session): array => [
                'id' => $session->id,
                'title' => $session->titre ?: 'Test d\'orientation',
                'score' => $session->score_global ? round((float) $session->score_global) : null,
                'completed_at' => $session->termine_le ?: $session->updated_at,
            ])
            ->all();
    }

    /**
     * Retourne les filieres recommandees les mieux scorees.
     */
    private function recommendedPrograms(int $userId): array
    {
        if (! $this->tableHasColumns('recommandations', ['id', 'user_id', 'filiere_id', 'score', 'rang'])
            || ! $this->tableHasColumns('filieres', ['id', 'nom', 'domaine', 'niveau'])) {
            return [];
        }

        return DB::table('recommandations')
            ->join('filieres', 'filieres.id', '=', 'recommandations.filiere_id')
            ->where('recommandations.user_id', $userId)
            ->orderByDesc('recommandations.score')
            ->orderBy('recommandations.rang')
            ->limit(4)
            ->get([
                'recommandations.id',
                'recommandations.score',
                'filieres.nom',
                'filieres.domaine',
                'filieres.niveau',
            ])
            ->map(fn ($recommendation): array => [
                'id' => $recommendation->id,
                'name' => $recommendation->nom,
                'domain' => $recommendation->domaine,
                'level' => $recommendation->niveau,
                'score' => round((float) $recommendation->score),
            ])
            ->all();
    }

    /**
     * Retourne les ecoles recommandees quand les recommandations pointent vers un etablissement.
     */
    private function recommendedSchools(int $userId): array
    {
        if (! $this->tableHasColumns('recommandations', ['id', 'user_id', 'etablissement_id', 'score'])
            || ! $this->tableHasColumns('etablissements', ['id', 'nom', 'ville', 'logo'])) {
            return [];
        }

        return DB::table('recommandations')
            ->join('etablissements', 'etablissements.id', '=', 'recommandations.etablissement_id')
            ->where('recommandations.user_id', $userId)
            ->whereNotNull('recommandations.etablissement_id')
            ->orderByDesc('recommandations.score')
            ->limit(3)
            ->get([
                'recommandations.id',
                'recommandations.score',
                'etablissements.nom',
                'etablissements.ville',
                'etablissements.logo',
            ])
            ->map(fn ($recommendation): array => [
                'id' => $recommendation->id,
                'name' => $recommendation->nom,
                'city' => $recommendation->ville,
                'logo_url' => $this->publicPhotoUrl($recommendation->logo),
                'score' => round((float) $recommendation->score),
            ])
            ->all();
    }

    /**
     * Retourne les derniers messages recus dans les conversations de l'etudiant.
     */
    private function recentMessages(int $userId): array
    {
        if (! $this->tableHasColumns('messages', ['id', 'conversation_id', 'expediteur_id', 'contenu', 'created_at', 'lu_le'])
            || ! $this->tableHasColumns('conversations', ['id', 'etudiant_id'])
            || ! $this->tableHasColumns('users', ['id', 'name'])) {
            return [];
        }

        return DB::table('messages')
            ->join('conversations', 'conversations.id', '=', 'messages.conversation_id')
            ->join('users', 'users.id', '=', 'messages.expediteur_id')
            ->where('conversations.etudiant_id', $userId)
            ->where('messages.expediteur_id', '!=', $userId)
            ->orderByDesc('messages.created_at')
            ->limit(3)
            ->get([
                'messages.id',
                'messages.contenu',
                'messages.created_at',
                'messages.lu_le',
                'users.name as sender_name',
            ])
            ->map(fn ($message): array => [
                'id' => $message->id,
                'sender' => $message->sender_name,
                'excerpt' => str($message->contenu)->limit(80)->toString(),
                'received_at' => $message->created_at,
                'is_unread' => $message->lu_le === null,
            ])
            ->all();
    }

    /**
     * Compte les messages non lus envoyes par d'autres utilisateurs.
     */
    private function unreadMessagesCount(int $userId): int
    {
        if (! $this->tableHasColumns('messages', ['conversation_id', 'expediteur_id', 'lu_le'])
            || ! $this->tableHasColumns('conversations', ['id', 'etudiant_id'])) {
            return 0;
        }

        return DB::table('messages')
            ->join('conversations', 'conversations.id', '=', 'messages.conversation_id')
            ->where('conversations.etudiant_id', $userId)
            ->where('messages.expediteur_id', '!=', $userId)
            ->whereNull('messages.lu_le')
            ->count();
    }

    /**
     * Retourne les notifications utiles pour les rappels et concours.
     */
    private function reminders(int $userId): array
    {
        if (! $this->tableHasColumns('notifications', ['id', 'user_id', 'titre', 'contenu', 'type', 'created_at'])) {
            return [];
        }

        return DB::table('notifications')
            ->where('user_id', $userId)
            ->whereIn('type', ['rappel', 'concours', 'bourse'])
            ->orderByDesc('created_at')
            ->limit(2)
            ->get(['id', 'titre', 'contenu', 'type', 'created_at'])
            ->map(fn ($notification): array => [
                'id' => $notification->id,
                'title' => $notification->titre,
                'content' => $notification->contenu,
                'type' => $notification->type,
                'date' => $notification->created_at,
            ])
            ->all();
    }

    /**
     * Retourne quelques domaines actifs a afficher en raccourci.
     */
    private function domains(): array
    {
        if (! $this->tableHasColumns('filieres', ['domaine', 'active'])) {
            return [];
        }

        return DB::table('filieres')
            ->where('active', true)
            ->select('domaine')
            ->distinct()
            ->orderBy('domaine')
            ->limit(6)
            ->pluck('domaine')
            ->map(fn (string $domain): array => [
                'name' => $domain,
                'slug' => str($domain)->slug()->toString(),
            ])
            ->all();
    }

    /**
     * Compte les tests termines sans casser si la base locale est incomplete.
     */
    private function completedTestsCount(int $userId): int
    {
        if (! $this->tableHasColumns('sessions_test', ['user_id', 'statut'])) {
            return 0;
        }

        return DB::table('sessions_test')
            ->where('user_id', $userId)
            ->where('statut', 'termine')
            ->count();
    }

    /**
     * Compte les ecoles favorites si la table favoris suit le schema courant.
     */
    private function favoriteSchoolsCount(int $userId): int
    {
        if (! $this->tableHasColumns('favoris', ['user_id', 'favoritable_type'])) {
            return 0;
        }

        return DB::table('favoris')
            ->where('user_id', $userId)
            ->where('favoritable_type', 'like', '%Etablissement%')
            ->count();
    }

    /**
     * Compte les conversations ouvertes si la table existe avec les bonnes colonnes.
     */
    private function openConversationsCount(int $userId): int
    {
        if (! $this->tableHasColumns('conversations', ['etudiant_id', 'statut'])) {
            return 0;
        }

        return DB::table('conversations')
            ->where('etudiant_id', $userId)
            ->where('statut', 'ouverte')
            ->count();
    }

    /**
     * Verifie la presence d'une table et de ses colonnes avant de lancer une requete.
     */
    private function tableHasColumns(string $table, array $columns): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Convertit les chemins stockes en URL publiques.
     */
    private function publicPhotoUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str($path)->startsWith(['http://', 'https://', '/'])) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
