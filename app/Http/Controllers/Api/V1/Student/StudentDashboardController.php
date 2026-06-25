<?php

// Commentaire d'intention: alimente l'espace etudiant, les tests, resultats, catalogues et actions de profil.

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Services\Orientation\PersonalityTestScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class StudentDashboardController extends Controller
{
    public function __construct(private readonly PersonalityTestScoringService $personalityScoring)
    {
    }

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
                'available_tests' => $this->availableTests($user->id),
                'test_results' => $this->testResults($user->id),
                'recent_tests' => $this->recentTests($user->id),
                'recommended_programs' => $this->recommendedPrograms($user->id),
                'recommended_schools' => $this->recommendedSchools($user->id),
                'catalog_programs' => $this->catalogPrograms(),
                'catalog_schools' => $this->catalogSchools(),
                'recent_messages' => $this->recentMessages($user->id),
                'reminders' => $this->reminders($user->id),
                'domains' => $this->domains(),
            ],
        ]);
    }

    /**
     * Affiche un test administrable pour que l'etudiant puisse le passer.
     */
    public function orientationTest(Request $request, int $test): JsonResponse
    {
        $user = $request->user();

        if (! $user->isEtudiant()) {
            return response()->json(['message' => 'Ce test est reserve aux etudiants.'], 403);
        }

        if (! $this->tableHasColumns('tests_orientation', ['id', 'titre', 'description', 'langue', 'version', 'duree_minutes', 'statut', 'updated_at'])) {
            return response()->json(['message' => 'Aucun test disponible pour le moment.'], 404);
        }

        $row = $this->studentVisibleTestQuery()->where('id', $test)->first();

        if (! $row) {
            return response()->json(['message' => 'Test introuvable ou indisponible.'], 404);
        }

        return response()->json([
            'data' => [
                ...$this->formatAvailableTest($row, $user->id),
                'questions' => $this->questionsForTest($test),
            ],
        ]);
    }

    /**
     * Enregistre les reponses de l'etudiant et calcule ses recommandations.
     */
    public function submitOrientationTest(Request $request, int $test): JsonResponse
    {
        $user = $request->user();

        if (! $user->isEtudiant()) {
            return response()->json(['message' => 'La soumission de test est reservee aux etudiants.'], 403);
        }

        if (! $this->tableHasColumns('tests_orientation', ['id', 'titre', 'description', 'langue', 'version', 'duree_minutes', 'statut', 'updated_at'])
            || ! $this->tableHasColumns('sessions_test', ['user_id', 'test_orientation_id', 'statut', 'score_global', 'commence_le', 'termine_le', 'metadata'])
            || ! $this->tableHasColumns('reponses_etudiants', ['session_test_id', 'question_id', 'choix_reponse_id', 'valeur'])) {
            return response()->json(['message' => 'Le moteur de test n\'est pas encore disponible.'], 503);
        }

        $row = $this->studentVisibleTestQuery()->where('id', $test)->first();

        if (! $row) {
            return response()->json(['message' => 'Test introuvable ou indisponible.'], 404);
        }

        $validated = $request->validate([
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.question_id' => ['required', 'integer'],
            'answers.*.choice_id' => ['nullable', 'integer'],
            'answers.*.choice_ids' => ['nullable', 'array'],
            'answers.*.choice_ids.*' => ['integer'],
        ]);

        $testQuestions = $this->questionsForTest((int) $row->id);

        if (count($testQuestions) === 0) {
            return response()->json(['message' => 'Ce test ne contient aucune question active.'], 422);
        }

        [$answers, $selectedChoiceIds] = $this->normalizeAnswers((int) $row->id, $validated['answers']);

        if (count($answers) === 0) {
            return response()->json(['message' => 'Aucune reponse valide pour ce test.'], 422);
        }

        $answeredQuestionIds = collect($answers)->pluck('question_id')->unique();
        $missingQuestionIds = collect($testQuestions)
            ->pluck('id')
            ->diff($answeredQuestionIds);

        if ($missingQuestionIds->isNotEmpty()) {
            return response()->json([
                'message' => 'Vous devez repondre a toutes les questions avant de valider le test.',
                'errors' => [
                    'answers' => ['Toutes les questions du test doivent avoir une reponse.'],
                ],
            ], 422);
        }

        [$sessionId, $score] = DB::transaction(function () use ($answers, $row, $selectedChoiceIds, $user): array {
            $now = now();
            $sessionId = DB::table('sessions_test')->insertGetId([
                'user_id' => $user->id,
                'test_orientation_id' => $row->id,
                'statut' => 'en_cours',
                'score_global' => null,
                'commence_le' => $now,
                'termine_le' => null,
                'metadata' => json_encode(['source' => 'student_dashboard'], JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($answers as $answer) {
                DB::table('reponses_etudiants')->insert([
                    'session_test_id' => $sessionId,
                    'question_id' => $answer['question_id'],
                    'choix_reponse_id' => $answer['choice_id'],
                    'reponse_libre' => null,
                    'valeur' => $answer['value'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $score = $this->scoreFromChoices((int) $row->id, $selectedChoiceIds);

            DB::table('sessions_test')->where('id', $sessionId)->update([
                'statut' => 'termine',
                'score_global' => $score,
                'termine_le' => $now,
                'updated_at' => $now,
            ]);

            return [$sessionId, $score];
        });

        $recommendations = $this->persistChoiceRecommendations($sessionId, $user->id, $selectedChoiceIds);

        if (count($recommendations) === 0) {
            $this->personalityScoring->persistRecommendations($sessionId);
            $recommendations = $this->recommendationsForSession($sessionId);
        }

        return response()->json([
            'message' => 'Test termine. Vos resultats sont disponibles.',
            'data' => [
                'session_id' => $sessionId,
                'score' => $score,
                'recommendations' => $recommendations,
            ],
        ], 201);
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
     * Tests crees dans l'administration et visibles dans l'espace etudiant.
     */
    private function availableTests(int $userId): array
    {
        if (! $this->tableHasColumns('tests_orientation', ['id', 'titre', 'description', 'langue', 'version', 'duree_minutes', 'statut', 'updated_at'])) {
            return [];
        }

        return $this->studentVisibleTestQuery()
            ->orderByRaw("case when statut = 'publie' then 0 when statut = 'brouillon' then 1 else 2 end")
            ->orderByDesc('updated_at')
            ->limit(24)
            ->get()
            ->map(fn ($test): array => $this->formatAvailableTest($test, $userId))
            ->all();
    }

    /**
     * Base de requete commune: l'etudiant voit les tests non archives ajoutes par l'admin.
     */
    private function studentVisibleTestQuery()
    {
        return DB::table('tests_orientation')
            ->where('statut', '!=', 'archive')
            ->select(['id', 'titre', 'description', 'langue', 'version', 'duree_minutes', 'statut', 'updated_at']);
    }

    /**
     * Resume d'un test pour les cartes du dashboard.
     */
    private function formatAvailableTest($test, int $userId): array
    {
        $questionCount = $this->tableHasColumns('questions', ['test_orientation_id', 'active'])
            ? DB::table('questions')->where('test_orientation_id', $test->id)->where('active', true)->count()
            : 0;
        $lastSession = $this->latestSessionForTest($userId, (int) $test->id);

        return [
            'id' => (int) $test->id,
            'title' => $test->titre,
            'description' => $test->description ?? '',
            'language' => $test->langue ?? 'fr',
            'version' => (int) ($test->version ?? 1),
            'duration_minutes' => (int) ($test->duree_minutes ?? 20),
            'status' => $test->statut ?? 'brouillon',
            'questions_count' => (int) $questionCount,
            'last_session' => $lastSession,
            'completed' => ($lastSession['status'] ?? null) === 'termine',
        ];
    }

    /**
     * Dernier passage d'un test donne par l'etudiant.
     */
    private function latestSessionForTest(int $userId, int $testId): ?array
    {
        if (! $this->tableHasColumns('sessions_test', ['id', 'user_id', 'test_orientation_id', 'statut', 'score_global', 'termine_le', 'updated_at'])) {
            return null;
        }

        $session = DB::table('sessions_test')
            ->where('user_id', $userId)
            ->where('test_orientation_id', $testId)
            ->orderByDesc('termine_le')
            ->orderByDesc('updated_at')
            ->first(['id', 'statut', 'score_global', 'termine_le', 'updated_at']);

        if (! $session) {
            return null;
        }

        return [
            'id' => (int) $session->id,
            'status' => $session->statut,
            'score' => $session->score_global !== null ? round((float) $session->score_global) : null,
            'completed_at' => $session->termine_le ?: $session->updated_at,
        ];
    }

    /**
     * Questions et choix actifs pour lancer un test.
     */
    private function questionsForTest(int $testId): array
    {
        if (! $this->tableHasColumns('questions', ['id', 'test_orientation_id', 'libelle', 'type', 'domaine', 'ordre', 'obligatoire', 'active'])
            || ! $this->tableHasColumns('choix_reponses', ['id', 'question_id', 'libelle', 'ordre', 'valeur'])) {
            return [];
        }

        return DB::table('questions')
            ->where('test_orientation_id', $testId)
            ->where('active', true)
            ->orderBy('ordre')
            ->get(['id', 'libelle', 'type', 'domaine', 'ordre', 'obligatoire'])
            ->map(function ($question): array {
                $choices = DB::table('choix_reponses')
                    ->where('question_id', $question->id)
                    ->orderBy('ordre')
                    ->get(['id', 'libelle', 'ordre', 'valeur'])
                    ->map(fn ($choice): array => [
                        'id' => (int) $choice->id,
                        'label' => $choice->libelle,
                        'order' => (int) $choice->ordre,
                        'value' => (int) $choice->valeur,
                    ])
                    ->all();

                return [
                    'id' => (int) $question->id,
                    'label' => $question->libelle,
                    'type' => $question->type,
                    'domain' => $question->domaine,
                    'order' => (int) $question->ordre,
                    'required' => (bool) $question->obligatoire,
                    'choices' => $choices,
                ];
            })
            ->all();
    }

    /**
     * Nettoie le payload de reponses et garantit que les choix appartiennent au test.
     */
    private function normalizeAnswers(int $testId, array $payload): array
    {
        $questions = collect($this->questionsForTest($testId))->keyBy('id');
        $answers = [];
        $selectedChoiceIds = [];

        foreach ($payload as $answer) {
            $questionId = (int) ($answer['question_id'] ?? 0);
            $question = $questions->get($questionId);

            if (! $question) {
                continue;
            }

            $allowedChoices = collect($question['choices'])->keyBy('id');
            $choiceIds = collect($answer['choice_ids'] ?? [])
                ->push($answer['choice_id'] ?? null)
                ->filter()
                ->map(fn ($choiceId): int => (int) $choiceId)
                ->unique()
                ->values();

            foreach ($choiceIds as $choiceId) {
                $choice = $allowedChoices->get($choiceId);

                if (! $choice) {
                    continue;
                }

                $answers[] = [
                    'question_id' => $questionId,
                    'choice_id' => $choiceId,
                    'value' => $choice['value'],
                ];
                $selectedChoiceIds[] = $choiceId;
            }
        }

        return [$answers, array_values(array_unique($selectedChoiceIds))];
    }

    /**
     * Score global simple: moyenne des valeurs choisies par rapport au maximum possible.
     */
    private function scoreFromChoices(int $testId, array $selectedChoiceIds): float
    {
        if (count($selectedChoiceIds) === 0 || ! $this->tableHasColumns('choix_reponses', ['id', 'question_id', 'valeur'])) {
            return 0;
        }

        $questions = DB::table('questions')
            ->where('test_orientation_id', $testId)
            ->where('active', true)
            ->pluck('id');

        $scoreTotal = 0;
        $answeredQuestions = 0;

        foreach ($questions as $questionId) {
            $maxValue = (float) DB::table('choix_reponses')->where('question_id', $questionId)->max('valeur');
            $selectedChoices = DB::table('choix_reponses')
                ->where('question_id', $questionId)
                ->whereIn('id', $selectedChoiceIds)
                ->get(['valeur']);

            if ($selectedChoices->isEmpty()) {
                continue;
            }

            $answeredQuestions++;
            $selectedValue = (float) $selectedChoices->sum('valeur');
            $scoreTotal += $maxValue > 0 ? min(100, ($selectedValue / $maxValue) * 100) : 100;
        }

        return $answeredQuestions > 0 ? round($scoreTotal / $answeredQuestions, 2) : 0;
    }

    /**
     * Cree des recommandations depuis les poids filieres configures par l'admin.
     */
    private function persistChoiceRecommendations(int $sessionId, int $userId, array $selectedChoiceIds): array
    {
        if (count($selectedChoiceIds) === 0
            || ! $this->tableHasColumns('poids_filieres', ['choix_reponse_id', 'filiere_id', 'poids'])
            || ! $this->tableHasColumns('recommandations', ['user_id', 'session_test_id', 'filiere_id', 'score', 'rang'])
            || ! $this->tableHasColumns('filieres', ['id', 'nom'])) {
            return [];
        }

        $rows = DB::table('poids_filieres')
            ->join('filieres', 'filieres.id', '=', 'poids_filieres.filiere_id')
            ->whereIn('poids_filieres.choix_reponse_id', $selectedChoiceIds)
            ->groupBy('filieres.id', 'filieres.nom')
            ->orderByDesc(DB::raw('sum(poids_filieres.poids)'))
            ->get([
                'filieres.id',
                'filieres.nom',
                DB::raw('sum(poids_filieres.poids) as total_weight'),
            ]);

        $total = (float) $rows->sum('total_weight');

        if ($total <= 0) {
            return [];
        }

        DB::table('recommandations')->where('session_test_id', $sessionId)->delete();

        $rank = 1;
        foreach ($rows as $row) {
            DB::table('recommandations')->insert([
                'user_id' => $userId,
                'session_test_id' => $sessionId,
                'filiere_id' => $row->id,
                'etablissement_id' => null,
                'score' => round(((float) $row->total_weight / $total) * 100, 2),
                'explication' => 'Recommandation calculee depuis les reponses du test.',
                'rang' => $rank++,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $this->recommendationsForSession($sessionId);
    }

    /**
     * Resultats recents avec recommandations lisibles.
     */
    private function testResults(int $userId): array
    {
        if (! $this->tableHasColumns('sessions_test', ['id', 'user_id', 'test_orientation_id', 'statut', 'score_global', 'termine_le', 'updated_at'])
            || ! $this->tableHasColumns('tests_orientation', ['id', 'titre'])) {
            return [];
        }

        return DB::table('sessions_test')
            ->join('tests_orientation', 'tests_orientation.id', '=', 'sessions_test.test_orientation_id')
            ->where('sessions_test.user_id', $userId)
            ->where('sessions_test.statut', 'termine')
            ->orderByDesc('sessions_test.termine_le')
            ->orderByDesc('sessions_test.updated_at')
            ->limit(6)
            ->get([
                'sessions_test.id',
                'sessions_test.score_global',
                'sessions_test.termine_le',
                'sessions_test.updated_at',
                'tests_orientation.titre',
            ])
            ->map(fn ($session): array => [
                'id' => (int) $session->id,
                'title' => $session->titre,
                'score' => $session->score_global !== null ? round((float) $session->score_global) : null,
                'completed_at' => $session->termine_le ?: $session->updated_at,
                'recommendations' => $this->recommendationsForSession((int) $session->id),
            ])
            ->all();
    }

    /**
     * Recommandations d'une session terminee.
     */
    private function recommendationsForSession(int $sessionId): array
    {
        if (! $this->tableHasColumns('recommandations', ['id', 'session_test_id', 'filiere_id', 'score', 'rang'])
            || ! $this->tableHasColumns('filieres', ['id', 'nom', 'domaine', 'niveau'])) {
            return [];
        }

        return DB::table('recommandations')
            ->join('filieres', 'filieres.id', '=', 'recommandations.filiere_id')
            ->where('recommandations.session_test_id', $sessionId)
            ->orderBy('recommandations.rang')
            ->orderByDesc('recommandations.score')
            ->limit(5)
            ->get([
                'recommandations.id',
                'recommandations.score',
                'filieres.nom',
                'filieres.domaine',
                'filieres.niveau',
            ])
            ->map(fn ($recommendation): array => [
                'id' => (int) $recommendation->id,
                'name' => $recommendation->nom,
                'domain' => $recommendation->domaine,
                'level' => $recommendation->niveau,
                'score' => round((float) $recommendation->score),
            ])
            ->all();
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
            return $this->schoolsFromRecommendedPrograms($userId);
        }

        $schools = DB::table('recommandations')
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

        return count($schools) > 0 ? $schools : $this->schoolsFromRecommendedPrograms($userId);
    }

    /**
     * Si les recommandations visent seulement des filieres, propose les ecoles qui les offrent.
     */
    private function schoolsFromRecommendedPrograms(int $userId): array
    {
        if (! $this->tableHasColumns('recommandations', ['user_id', 'filiere_id', 'score'])
            || ! $this->tableHasColumns('etablissement_filiere', ['etablissement_id', 'filiere_id'])
            || ! $this->tableHasColumns('etablissements', ['id', 'nom', 'ville', 'logo', 'valide', 'statut'])) {
            return [];
        }

        $topFiliereIds = DB::table('recommandations')
            ->where('user_id', $userId)
            ->orderByDesc('score')
            ->limit(3)
            ->pluck('filiere_id');

        if ($topFiliereIds->isEmpty()) {
            return [];
        }

        return DB::table('etablissement_filiere')
            ->join('etablissements', 'etablissements.id', '=', 'etablissement_filiere.etablissement_id')
            ->whereIn('etablissement_filiere.filiere_id', $topFiliereIds)
            ->where(function ($query): void {
                $query->where('etablissements.valide', true)->orWhere('etablissements.statut', 'valide');
            })
            ->select('etablissements.id', 'etablissements.nom', 'etablissements.ville', 'etablissements.logo', DB::raw('count(*) as matches'))
            ->groupBy('etablissements.id', 'etablissements.nom', 'etablissements.ville', 'etablissements.logo')
            ->orderByDesc('matches')
            ->limit(3)
            ->get()
            ->map(fn ($school): array => [
                'id' => (int) $school->id,
                'name' => $school->nom,
                'city' => $school->ville,
                'logo_url' => $this->publicPhotoUrl($school->logo),
                'score' => 80,
            ])
            ->all();
    }

    /**
     * Catalogue consultable de filieres actives.
     */
    private function catalogPrograms(): array
    {
        if (! $this->tableHasColumns('filieres', ['id', 'nom', 'domaine', 'description', 'niveau', 'duree_annees', 'diplome_obtenu', 'active'])) {
            return [];
        }

        return DB::table('filieres')
            ->where('active', true)
            ->orderBy('domaine')
            ->orderBy('nom')
            ->limit(12)
            ->get(['id', 'nom', 'domaine', 'description', 'niveau', 'duree_annees', 'diplome_obtenu'])
            ->map(fn ($program): array => [
                'id' => (int) $program->id,
                'name' => $program->nom,
                'domain' => $program->domaine,
                'description' => str($program->description ?? '')->limit(110)->toString(),
                'level' => $program->niveau,
                'duration_years' => (int) $program->duree_annees,
                'diploma' => $program->diplome_obtenu,
                'schools_count' => $this->programSchoolsCount((int) $program->id),
            ])
            ->all();
    }

    /**
     * Nombre d'ecoles proposant une filiere.
     */
    private function programSchoolsCount(int $programId): int
    {
        if (! $this->tableHasColumns('etablissement_filiere', ['filiere_id'])) {
            return 0;
        }

        return DB::table('etablissement_filiere')->where('filiere_id', $programId)->count();
    }

    /**
     * Catalogue consultable d'ecoles validees.
     */
    private function catalogSchools(): array
    {
        if (! $this->tableHasColumns('etablissements', ['id', 'nom', 'type', 'ville', 'region', 'logo', 'description', 'valide', 'statut'])) {
            return [];
        }

        return DB::table('etablissements')
            ->where(function ($query): void {
                $query->where('valide', true)->orWhere('statut', 'valide');
            })
            ->orderBy('nom')
            ->limit(12)
            ->get(['id', 'nom', 'type', 'ville', 'region', 'logo', 'description'])
            ->map(fn ($school): array => [
                'id' => (int) $school->id,
                'name' => $school->nom,
                'type' => $school->type,
                'city' => $school->ville,
                'region' => $school->region,
                'description' => str($school->description ?? '')->limit(110)->toString(),
                'logo_url' => $this->publicPhotoUrl($school->logo),
                'programs_count' => $this->schoolProgramsCount((int) $school->id),
                'url' => '#etablissement-'.$school->id,
            ])
            ->all();
    }

    /**
     * Nombre de filieres proposees par une ecole.
     */
    private function schoolProgramsCount(int $schoolId): int
    {
        if (! $this->tableHasColumns('etablissement_filiere', ['etablissement_id'])) {
            return 0;
        }

        return DB::table('etablissement_filiere')->where('etablissement_id', $schoolId)->count();
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
