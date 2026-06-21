<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class OrientationTestController extends Controller
{
    public function __construct(private readonly ActivityLogService $activityLog)
    {
    }

    /**
     * Liste les tests avec leur volume de questions et de passages.
     */
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Action reservee aux administrateurs.'], 403);
        }

        if (! $this->tableHasColumns('tests_orientation', ['id', 'titre', 'description', 'langue', 'version', 'duree_minutes', 'statut', 'created_at'])) {
            return response()->json(['data' => []]);
        }

        $tests = DB::table('tests_orientation')
            ->leftJoin('questions', 'questions.test_orientation_id', '=', 'tests_orientation.id')
            ->leftJoin('sessions_test', 'sessions_test.test_orientation_id', '=', 'tests_orientation.id')
            ->groupBy('tests_orientation.id')
            ->orderByDesc('tests_orientation.updated_at')
            ->get([
                'tests_orientation.id',
                'tests_orientation.titre',
                'tests_orientation.description',
                'tests_orientation.langue',
                'tests_orientation.version',
                'tests_orientation.duree_minutes',
                'tests_orientation.statut',
                'tests_orientation.created_at',
                DB::raw('count(distinct questions.id) as questions_count'),
                DB::raw('count(distinct sessions_test.id) as sessions_count'),
            ])
            ->map(fn ($test): array => $this->formatTestRow($test))
            ->all();

        return response()->json(['data' => $tests]);
    }

    /**
     * Affiche un test avec ses questions, choix et poids.
     */
    public function show(Request $request, int $test): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Action reservee aux administrateurs.'], 403);
        }

        $row = DB::table('tests_orientation')->where('id', $test)->first();

        if (! $row) {
            return response()->json(['message' => 'Test introuvable.'], 404);
        }

        return response()->json([
            'data' => [
                ...$this->formatTestRow($row),
                'questions' => $this->questionsForTest($test),
            ],
        ]);
    }

    /**
     * Cree un nouveau test. Les questions sont optionnelles dans cette premiere interface admin.
     */
    public function store(Request $request): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Action reservee aux administrateurs.'], 403);
        }

        $validated = $this->validatedPayload($request);

        $testId = DB::transaction(function () use ($validated, $request): int {
            $now = now();
            $testId = DB::table('tests_orientation')->insertGetId([
                'titre' => $validated['titre'],
                'description' => $validated['description'] ?? null,
                'langue' => $validated['langue'] ?? 'fr',
                'version' => $validated['version'] ?? 1,
                'duree_minutes' => $validated['duree_minutes'] ?? 20,
                'statut' => $validated['statut'] ?? 'brouillon',
                'cree_par' => $request->user()->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $this->replaceQuestions($testId, $validated['questions'] ?? []);

            return $testId;
        });

        $this->activityLog->record(
            $request->user(),
            'Creation d’un test d’orientation',
            "Le test {$validated['titre']} a ete cree.",
            $request,
            ['target_type' => 'test_orientation', 'target_id' => $testId, 'status' => $validated['statut'] ?? 'brouillon']
        );

        return response()->json([
            'message' => 'Test cree avec succes.',
            'data' => $this->testWithCounts($testId),
        ], 201);
    }

    /**
     * Met a jour les informations du test et remplace les questions si elles sont fournies.
     */
    public function update(Request $request, int $test): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Action reservee aux administrateurs.'], 403);
        }

        if (! DB::table('tests_orientation')->where('id', $test)->exists()) {
            return response()->json(['message' => 'Test introuvable.'], 404);
        }

        $validated = $this->validatedPayload($request, true);

        DB::transaction(function () use ($validated, $test): void {
            DB::table('tests_orientation')->where('id', $test)->update([
                'titre' => $validated['titre'],
                'description' => $validated['description'] ?? null,
                'langue' => $validated['langue'] ?? 'fr',
                'version' => $validated['version'] ?? 1,
                'duree_minutes' => $validated['duree_minutes'] ?? 20,
                'statut' => $validated['statut'] ?? 'brouillon',
                'updated_at' => now(),
            ]);

            if (array_key_exists('questions', $validated)) {
                $this->replaceQuestions($test, $validated['questions'] ?? []);
            }
        });

        $this->activityLog->record(
            $request->user(),
            'Mise a jour d’un test d’orientation',
            "Le test {$validated['titre']} a ete mis a jour.",
            $request,
            ['target_type' => 'test_orientation', 'target_id' => $test, 'status' => $validated['statut'] ?? 'brouillon']
        );

        return response()->json([
            'message' => 'Test mis a jour.',
            'data' => $this->testWithCounts($test),
        ]);
    }

    /**
     * Supprime le test et les donnees liees pour eviter les contraintes orphelines.
     */
    public function destroy(Request $request, int $test): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Action reservee aux administrateurs.'], 403);
        }

        if (! DB::table('tests_orientation')->where('id', $test)->exists()) {
            return response()->json(['message' => 'Test introuvable.'], 404);
        }

        $testTitle = DB::table('tests_orientation')->where('id', $test)->value('titre');

        DB::transaction(function () use ($test): void {
            $questionIds = DB::table('questions')->where('test_orientation_id', $test)->pluck('id');
            $choiceIds = DB::table('choix_reponses')->whereIn('question_id', $questionIds)->pluck('id');
            $sessionIds = DB::table('sessions_test')->where('test_orientation_id', $test)->pluck('id');

            if ($sessionIds->isNotEmpty()) {
                DB::table('recommandations')->whereIn('session_test_id', $sessionIds)->delete();
                DB::table('reponses_etudiants')->whereIn('session_test_id', $sessionIds)->delete();
                DB::table('sessions_test')->whereIn('id', $sessionIds)->delete();
            }

            if ($choiceIds->isNotEmpty()) {
                DB::table('poids_filieres')->whereIn('choix_reponse_id', $choiceIds)->delete();
                DB::table('choix_reponses')->whereIn('id', $choiceIds)->delete();
            }

            if ($questionIds->isNotEmpty()) {
                DB::table('questions')->whereIn('id', $questionIds)->delete();
            }

            DB::table('tests_orientation')->where('id', $test)->delete();
        });

        $this->activityLog->record(
            $request->user(),
            'Suppression d’un test d’orientation',
            "Le test {$testTitle} a ete supprime.",
            $request,
            ['target_type' => 'test_orientation', 'target_id' => $test]
        );

        return response()->json(['message' => 'Test supprime.']);
    }

    /**
     * Valide les champs administrables du test.
     */
    private function validatedPayload(Request $request, bool $isUpdate = false): array
    {
        return $request->validate([
            'titre' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'langue' => ['nullable', Rule::in(['fr', 'en'])],
            'version' => ['nullable', 'integer', 'min:1'],
            'duree_minutes' => ['nullable', 'integer', 'min:1', 'max:240'],
            'statut' => ['nullable', Rule::in(['brouillon', 'publie', 'archive'])],
            'questions' => ['nullable', 'array'],
            'questions.*.libelle' => ['required_with:questions', 'string'],
            'questions.*.type' => ['nullable', Rule::in(['choix_unique', 'choix_multiple', 'echelle'])],
            'questions.*.domaine' => ['nullable', 'string', 'max:255'],
            'questions.*.ordre' => ['nullable', 'integer', 'min:1'],
            'questions.*.obligatoire' => ['nullable', 'boolean'],
            'questions.*.active' => ['nullable', 'boolean'],
            'questions.*.choices' => ['nullable', 'array'],
            'questions.*.choices.*.libelle' => ['required_with:questions.*.choices', 'string', 'max:255'],
            'questions.*.choices.*.ordre' => ['nullable', 'integer', 'min:1'],
            'questions.*.choices.*.valeur' => ['nullable', 'numeric'],
            'questions.*.choices.*.metadata' => ['nullable', 'array'],
            'questions.*.choices.*.weights' => ['nullable', 'array'],
            'questions.*.choices.*.weights.*' => ['numeric', 'min:0'],
        ]);
    }

    /**
     * Remplace toutes les questions d'un test par le payload fourni.
     */
    private function replaceQuestions(int $testId, array $questions): void
    {
        $this->deleteQuestions($testId);

        foreach ($questions as $questionIndex => $question) {
            $questionId = DB::table('questions')->insertGetId([
                'test_orientation_id' => $testId,
                'libelle' => $question['libelle'],
                'type' => $question['type'] ?? 'choix_unique',
                'domaine' => $question['domaine'] ?? null,
                'ordre' => $question['ordre'] ?? $questionIndex + 1,
                'obligatoire' => $question['obligatoire'] ?? true,
                'active' => $question['active'] ?? true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach (($question['choices'] ?? []) as $choiceIndex => $choice) {
                $choiceId = DB::table('choix_reponses')->insertGetId([
                    'question_id' => $questionId,
                    'libelle' => $choice['libelle'],
                    'ordre' => $choice['ordre'] ?? $choiceIndex + 1,
                    'valeur' => $choice['valeur'] ?? 0,
                    'metadata' => isset($choice['metadata']) ? json_encode($choice['metadata'], JSON_UNESCAPED_UNICODE) : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->storeChoiceWeights($choiceId, $choice['weights'] ?? []);
            }
        }
    }

    /**
     * Supprime les questions/choix/poids d'un test avant remplacement.
     */
    private function deleteQuestions(int $testId): void
    {
        $questionIds = DB::table('questions')->where('test_orientation_id', $testId)->pluck('id');
        $choiceIds = DB::table('choix_reponses')->whereIn('question_id', $questionIds)->pluck('id');

        if ($choiceIds->isNotEmpty()) {
            DB::table('poids_filieres')->whereIn('choix_reponse_id', $choiceIds)->delete();
            DB::table('choix_reponses')->whereIn('id', $choiceIds)->delete();
        }

        if ($questionIds->isNotEmpty()) {
            DB::table('questions')->whereIn('id', $questionIds)->delete();
        }
    }

    /**
     * Enregistre les poids par filiere quand le payload fournit des slugs sci/lit/etc.
     */
    private function storeChoiceWeights(int $choiceId, array $weights): void
    {
        $filieres = [
            'sci' => 'Scientifique',
            'lit' => 'Litteraire',
            'tech' => 'Technique',
            'com' => 'Commercial',
            'sante' => 'Sante',
        ];

        foreach ($weights as $slug => $weight) {
            if ((float) $weight <= 0 || ! isset($filieres[$slug])) {
                continue;
            }

            $filiereId = DB::table('filieres')->where('nom', $filieres[$slug])->value('id');

            if (! $filiereId) {
                continue;
            }

            DB::table('poids_filieres')->insert([
                'choix_reponse_id' => $choiceId,
                'filiere_id' => $filiereId,
                'poids' => $weight,
                'justification' => "Poids {$slug} defini par l'administrateur",
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Charge un test avec les compteurs utiles apres creation ou modification.
     */
    private function testWithCounts(int $testId): array
    {
        $row = DB::table('tests_orientation')
            ->leftJoin('questions', 'questions.test_orientation_id', '=', 'tests_orientation.id')
            ->leftJoin('sessions_test', 'sessions_test.test_orientation_id', '=', 'tests_orientation.id')
            ->where('tests_orientation.id', $testId)
            ->groupBy('tests_orientation.id')
            ->first([
                'tests_orientation.id',
                'tests_orientation.titre',
                'tests_orientation.description',
                'tests_orientation.langue',
                'tests_orientation.version',
                'tests_orientation.duree_minutes',
                'tests_orientation.statut',
                'tests_orientation.created_at',
                DB::raw('count(distinct questions.id) as questions_count'),
                DB::raw('count(distinct sessions_test.id) as sessions_count'),
            ]);

        return $this->formatTestRow($row);
    }

    /**
     * Retourne les questions detaillees du test.
     */
    private function questionsForTest(int $testId): array
    {
        $questions = DB::table('questions')
            ->where('test_orientation_id', $testId)
            ->orderBy('ordre')
            ->get();

        return $questions->map(function ($question): array {
            $choices = DB::table('choix_reponses')
                ->where('question_id', $question->id)
                ->orderBy('ordre')
                ->get()
                ->map(fn ($choice): array => [
                    'id' => $choice->id,
                    'libelle' => $choice->libelle,
                    'ordre' => $choice->ordre,
                    'valeur' => $choice->valeur,
                    'metadata' => $choice->metadata ? json_decode($choice->metadata, true) : null,
                    'weights' => $this->weightsForChoice($choice->id),
                ])
                ->all();

            return [
                'id' => $question->id,
                'libelle' => $question->libelle,
                'type' => $question->type,
                'domaine' => $question->domaine,
                'ordre' => $question->ordre,
                'obligatoire' => (bool) $question->obligatoire,
                'active' => (bool) $question->active,
                'choices' => $choices,
            ];
        })->all();
    }

    /**
     * Retourne les poids lisibles pour un choix de reponse.
     */
    private function weightsForChoice(int $choiceId): array
    {
        $weights = DB::table('poids_filieres')
            ->join('filieres', 'filieres.id', '=', 'poids_filieres.filiere_id')
            ->where('poids_filieres.choix_reponse_id', $choiceId)
            ->pluck('poids_filieres.poids', 'filieres.nom')
            ->all();

        $normalized = [];

        foreach ($weights as $name => $weight) {
            $slug = $this->slugForFiliere($name);

            if ($slug) {
                $normalized[$slug] = (float) $weight;
            }
        }

        return $normalized;
    }

    /**
     * Convertit les noms de filieres en codes utilises par l'algorithme.
     */
    private function slugForFiliere(string $name): ?string
    {
        return [
            'Scientifique' => 'sci',
            'Litteraire' => 'lit',
            'Technique' => 'tech',
            'Commercial' => 'com',
            'Sante' => 'sante',
        ][$name] ?? null;
    }

    /**
     * Harmonise la sortie JSON d'un test.
     */
    private function formatTestRow($test): array
    {
        return [
            'id' => $test->id,
            'title' => $test->titre,
            'description' => $test->description ?? '',
            'language' => $test->langue ?? 'fr',
            'version' => (int) ($test->version ?? 1),
            'duration_minutes' => (int) ($test->duree_minutes ?? 20),
            'status' => $test->statut ?? 'brouillon',
            'questions_count' => (int) ($test->questions_count ?? 0),
            'sessions_count' => (int) ($test->sessions_count ?? 0),
            'created_at' => $test->created_at ?? null,
        ];
    }

    /**
     * Verifie que la table et ses colonnes existent avant de requeter.
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
}
