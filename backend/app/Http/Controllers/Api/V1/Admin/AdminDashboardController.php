<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Admin\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminDashboardController extends Controller
{
    public function __construct(private readonly ActivityLogService $activityLog)
    {
    }

    /**
     * Retourne les indicateurs principaux du tableau de bord administrateur.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isAdmin()) {
            return response()->json([
                'message' => 'Ce tableau de bord est reservé aux administrateurs.',
            ], 403);
        }

        return response()->json([
            'data' => [
                'admin' => [
                    'name' => $user->name,
                    'role_label' => 'Super administrateur',
                ],
                'metrics' => [
                    'students' => $this->countUsersByRole('etudiant'),// pour afficher le nombre étudiants
                    'active_counselors' => $this->countUsersByRole('conseiller', 'actif'),// pour afficher uniquement les conseillers actifs
                    'pending_accounts' => $this->countUsersByStatus('en_attente'),
                    'tests_completed' => $this->countCompletedTests(),
                    'schools' => $this->countSchools(),
                    'open_reports' => $this->countOpenReports(),
                ],
                'pending_accounts' => $this->pendingAccounts(),
                'popular_tests' => $this->popularTests(),
                'recent_activity' => $this->recentActivity(),
                'user_distribution' => $this->userDistribution(),
                'system_alerts' => $this->systemAlerts(),
            ],
        ]);
    }

    /**
     * Valide, rejette ou suspend un compte depuis le tableau de bord admin.
     */
    public function updateAccountStatus(Request $request, User $user): JsonResponse
    {
        $admin = $request->user();

        if (! $admin->isAdmin()) {
            return response()->json([
                'message' => 'Action reservee aux administrateurs.',
            ], 403);
        }

        $validated = $request->validate([
            'statut' => ['required', 'in:actif,rejete,suspendu,en_attente'],
            'commentaire_admin' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($user, $admin, $validated): void {
            $user->forceFill(['statut' => $validated['statut']])->save();

            if ($user->isConseiller() && Schema::hasTable('validations_conseillers')) {
                $validationStatus = match ($validated['statut']) {
                    'actif' => 'approuve',
                    'rejete' => 'rejete',
                    'suspendu' => 'suspendu',
                    default => 'en_attente',
                };
                $validationId = DB::table('validations_conseillers')
                    ->where('conseiller_id', $user->id)
                    ->orderByDesc('id')
                    ->value('id');

                if ($validationId) {
                    DB::table('validations_conseillers')->where('id', $validationId)->update([
                        'statut' => $validationStatus,
                        'commentaire_admin' => $validated['commentaire_admin'] ?? null,
                        'traite_par' => $admin->id,
                        'traite_le' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        $this->activityLog->record(
            $admin,
            'Mise a jour du statut d’un compte',
            "Le statut du compte {$user->name} a ete defini sur {$validated['statut']}.",
            $request,
            ['target_type' => 'user', 'target_id' => $user->id, 'new_status' => $validated['statut']]
        );

        return response()->json([
            'message' => 'Statut du compte mis a jour.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
                'statut' => $validated['statut'],
            ],
        ]);
    }

    /**
     * Retourne le journal persistant des actions administratives.
     */
    public function activityLogs(Request $request): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Action reservee aux administrateurs.'], 403);
        }

        if (! $this->tableHasColumns('logs_admin', ['id', 'admin_id', 'action', 'metadata', 'created_at'])) {
            return response()->json(['data' => []]);
        }

        $limit = min(max((int) $request->integer('limit', 50), 1), 200);
        $logs = DB::table('logs_admin')
            ->leftJoin('users', 'users.id', '=', 'logs_admin.admin_id')
            ->orderByDesc('logs_admin.created_at')
            ->limit($limit)
            ->get([
                'logs_admin.id',
                'logs_admin.action',
                'logs_admin.metadata',
                'logs_admin.created_at',
                'users.name as admin_name',
                'users.email as admin_email',
            ])
            ->map(function ($log): array {
                $metadata = is_string($log->metadata) ? json_decode($log->metadata, true) : $log->metadata;
                $detail = is_array($metadata) ? ($metadata['detail'] ?? null) : null;

                return [
                    'id' => (int) $log->id,
                    'date' => $log->created_at,
                    'user' => $log->admin_name ?? 'Systeme',
                    'email' => $log->admin_email,
                    'action' => $log->action,
                    'detail' => $detail ?? 'Aucun detail disponible pour cette action.',
                ];
            })
            ->all();

        return response()->json(['data' => $logs]);
    }

    /**
     * Liste complete des etudiants inscrits.
     */
    public function students(Request $request): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Action reservee aux administrateurs.'], 403);
        }

        if (! $this->tableHasColumns('users', ['id', 'name', 'email', 'role', 'statut', 'created_at'])) {
            return response()->json(['data' => []]);
        }

        $rows = DB::table('users')
            ->leftJoin('profils_etudiants', 'profils_etudiants.user_id', '=', 'users.id')
            ->where('users.role', 'etudiant')
            ->orderByDesc('users.created_at')
            ->get([
                'users.id',
                'users.name',
                'users.email',
                'users.telephone',
                'users.statut',
                'users.created_at',
                'profils_etudiants.ville',
                'profils_etudiants.region',
                'profils_etudiants.type_bac',
                'profils_etudiants.annee_bac',
            ])
            ->map(fn ($student): array => [
                'id' => (int) $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'phone' => $student->telephone,
                'status' => $student->statut,
                'city' => $student->ville,
                'region' => $student->region,
                'bac' => $student->type_bac,
                'bac_year' => $student->annee_bac,
                'created_at' => $student->created_at,
            ])
            ->all();

        return response()->json(['data' => $rows]);
    }

    /**
     * Liste des conseillers actifs.
     */
    public function counselors(Request $request): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Action reservee aux administrateurs.'], 403);
        }

        if (! $this->tableHasColumns('users', ['id', 'name', 'email', 'role', 'statut', 'created_at'])) {
            return response()->json(['data' => []]);
        }

        $rows = DB::table('users')
            ->leftJoin('profils_conseillers', 'profils_conseillers.user_id', '=', 'users.id')
            ->where('users.role', 'conseiller')
            ->where('users.statut', 'actif')
            ->orderByDesc('users.created_at')
            ->get([
                'users.id',
                'users.name',
                'users.email',
                'users.telephone',
                'users.statut',
                'profils_conseillers.specialite',
                'profils_conseillers.ville',
                'profils_conseillers.note_moyenne',
                'profils_conseillers.nb_etudiants_accompagnes',
            ])
            ->map(fn ($counselor): array => [
                'id' => (int) $counselor->id,
                'name' => $counselor->name,
                'email' => $counselor->email,
                'phone' => $counselor->telephone,
                'status' => $counselor->statut,
                'specialty' => $counselor->specialite,
                'city' => $counselor->ville,
                'rating' => $counselor->note_moyenne ? (float) $counselor->note_moyenne : null,
                'students_count' => (int) ($counselor->nb_etudiants_accompagnes ?? 0),
            ])
            ->all();

        return response()->json(['data' => $rows]);
    }

    /**
     * Liste detaillee des comptes en attente de validation.
     */
    public function pendingAccountsList(Request $request): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Action reservee aux administrateurs.'], 403);
        }

        if (! $this->tableHasColumns('users', ['id', 'name', 'email', 'role', 'statut', 'created_at'])) {
            return response()->json(['data' => []]);
        }

        $rows = DB::table('users')
            ->where('statut', 'en_attente')
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'email', 'role', 'telephone', 'statut', 'created_at'])
            ->map(fn ($account): array => [
                'id' => (int) $account->id,
                'name' => $account->name,
                'email' => $account->email,
                'role' => $account->role,
                'phone' => $account->telephone,
                'status' => $account->statut,
                'created_at' => $account->created_at,
            ])
            ->all();

        return response()->json(['data' => $rows]);
    }

    /**
     * Liste chaque passage de test avec l'etudiant concerne.
     */
    public function testSessions(Request $request): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Action reservee aux administrateurs.'], 403);
        }

        if (! $this->tableHasColumns('sessions_test', ['id', 'user_id', 'test_orientation_id', 'statut', 'score_global', 'termine_le'])
            || ! $this->tableHasColumns('tests_orientation', ['id', 'titre'])
            || ! $this->tableHasColumns('users', ['id', 'name', 'email'])) {
            return response()->json(['data' => []]);
        }

        $rows = DB::table('sessions_test')
            ->join('users', 'users.id', '=', 'sessions_test.user_id')
            ->join('tests_orientation', 'tests_orientation.id', '=', 'sessions_test.test_orientation_id')
            ->where('sessions_test.statut', 'termine')
            ->orderByDesc('sessions_test.termine_le')
            ->orderByDesc('sessions_test.updated_at')
            ->get([
                'sessions_test.id',
                'sessions_test.score_global',
                'sessions_test.termine_le',
                'tests_orientation.titre',
                'users.name',
                'users.email',
            ])
            ->map(fn ($session): array => [
                'id' => (int) $session->id,
                'test' => $session->titre,
                'student' => $session->name,
                'email' => $session->email,
                'score' => $session->score_global ? round((float) $session->score_global, 2) : null,
                'completed_at' => $session->termine_le,
            ])
            ->all();

        return response()->json(['data' => $rows]);
    }

    /**
     * Liste les ecoles avec filieres, image et lien.
     */
    public function schools(Request $request): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Action reservee aux administrateurs.'], 403);
        }

        if (! Schema::hasTable('etablissements')) {
            return response()->json(['data' => []]);
        }

        $rows = DB::table('etablissements')
            ->leftJoin('etablissement_filiere', 'etablissement_filiere.etablissement_id', '=', 'etablissements.id')
            ->leftJoin('filieres', 'filieres.id', '=', 'etablissement_filiere.filiere_id')
            ->groupBy(
                'etablissements.id',
                'etablissements.nom',
                'etablissements.ville',
                'etablissements.logo',
                'etablissements.site_web',
                'etablissements.statut',
                'etablissements.valide'
            )
            ->orderBy('etablissements.nom')
            ->get([
                'etablissements.id',
                'etablissements.nom',
                'etablissements.ville',
                'etablissements.logo',
                'etablissements.site_web',
                'etablissements.statut',
                'etablissements.valide',
                DB::raw("string_agg(distinct filieres.nom, ', ') as filieres"),
            ])
            ->map(fn ($school): array => [
                'id' => (int) $school->id,
                'name' => $school->nom,
                'city' => $school->ville,
                'filieres' => $school->filieres
                    ? array_map(fn ($name): string => $this->displayFiliereName($name), explode(', ', $school->filieres))
                    : [],
                'image' => $school->logo,
                'image_url' => $this->schoolImageUrl($school->logo),
                'website' => $school->site_web,
                'status' => $school->statut,
                'validated' => (bool) $school->valide,
            ])
            ->all();

        return response()->json(['data' => $rows]);
    }

    /**
     * Met a jour les champs administrables d'une ecole depuis le tableau.
     */
    public function updateSchool(Request $request, int $school): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Action reservee aux administrateurs.'], 403);
        }

        if (! Schema::hasTable('etablissements') || ! DB::table('etablissements')->where('id', $school)->exists()) {
            return response()->json(['message' => 'Ecole introuvable.'], 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'string', 'max:1000'],
            'website' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'in:en_attente,valide,rejete'],
            'validated' => ['nullable', 'boolean'],
        ]);

        DB::table('etablissements')->where('id', $school)->update([
            'nom' => $validated['name'],
            'ville' => $validated['city'] ?? null,
            'logo' => $validated['image'] ?? null,
            'site_web' => $validated['website'] ?? null,
            'statut' => $validated['status'] ?? 'en_attente',
            'valide' => $validated['validated'] ?? (($validated['status'] ?? null) === 'valide'),
            'updated_at' => now(),
        ]);

        $this->activityLog->record(
            $request->user(),
            'Mise a jour d’un etablissement',
            "L’etablissement {$validated['name']} a ete mis a jour.",
            $request,
            ['target_type' => 'etablissement', 'target_id' => $school, 'status' => $validated['status'] ?? 'en_attente']
        );

        return response()->json([
            'message' => 'Ecole mise a jour.',
            'data' => ['id' => $school],
        ]);
    }

    /**
     * Compte les utilisateurs par role, avec filtre de statut optionnel.
     */
    private function countUsersByRole(string $role, ?string $status = null): int
    {
        if (! $this->tableHasColumns('users', ['role'])) {
            return 0;
        }

        $query = DB::table('users')->where('role', $role);

        if ($status && Schema::hasColumn('users', 'statut')) {
            $query->where('statut', $status);
        }

        return $query->count();
    }

    /**
     * Compte les comptes selon leur statut administratif.
     */
    private function countUsersByStatus(string $status): int
    {
        if (! $this->tableHasColumns('users', ['statut'])) {
            return 0;
        }

        return DB::table('users')->where('statut', $status)->count();
    }

    /**
     * Compte les sessions de test terminees, si la table existe.
     */
    private function countCompletedTests(): int
    {
        if (! $this->tableHasColumns('sessions_test', ['statut'])) {
            return 0;
        }

        return DB::table('sessions_test')->where('statut', 'termine')->count();
    }

    /**
     * Compte les etablissements presents dans le catalogue.
     */
    private function countSchools(): int
    {
        if (! Schema::hasTable('etablissements')) {
            return 0;
        }

        return DB::table('etablissements')->count();
    }

    /**
     * Masque le prefixe technique "CODE - " utilise pour eviter les collisions internes.
     */
    private function displayFiliereName(string $name): string
    {
        return preg_replace('/^.+? - /', '', $name) ?: $name;
    }

    /**
     * Construit une URL affichable aussi bien pour une image locale que distante.
     */
    private function schoolImageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str($path)->startsWith(['http://', 'https://'])) {
            return $path;
        }

        if (str($path)->startsWith('images/')) {
            return asset($path);
        }

        return asset('storage/' . ltrim($path, '/'));
    }

    /**
     * Compte les signalements ouverts ou en attente de traitement.
     */
    private function countOpenReports(): int
    {
        if (! $this->tableHasColumns('signalements', ['statut'])) {
            return 0;
        }

        return DB::table('signalements')->whereIn('statut', ['ouvert', 'en_attente'])->count();
    }

    /**
     * Liste les derniers comptes qui attendent une validation admin.
     */
    private function pendingAccounts(): array
    {
        if (! $this->tableHasColumns('users', ['id', 'name', 'role', 'statut', 'created_at'])) {
            return [];
        }

        return DB::table('users')
            ->where('statut', 'en_attente')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'name', 'role', 'created_at'])
            ->map(fn ($account): array => [
                'id' => $account->id,
                'name' => $account->name,
                'role' => $account->role,
                'date' => $account->created_at,
                'type' => $account->role === 'conseiller' ? 'Documents' : 'Informations',
            ])
            ->all();
    }

    /**
     * Calcule les tests les plus passes et leur score moyen.
     */
    private function popularTests(): array
    {
        if (! $this->tableHasColumns('sessions_test', ['test_orientation_id'])
            || ! $this->tableHasColumns('tests_orientation', ['id', 'titre'])) {
            return [];
        }

        return DB::table('sessions_test')
            ->join('tests_orientation', 'tests_orientation.id', '=', 'sessions_test.test_orientation_id')
            ->select('tests_orientation.titre', DB::raw('count(*) as passages'), DB::raw('avg(sessions_test.score_global) as average_score'))
            ->groupBy('tests_orientation.id', 'tests_orientation.titre')
            ->orderByDesc('passages')
            ->limit(5)
            ->get()
            ->map(fn ($test): array => [
                'title' => $test->titre,
                'passages' => (int) $test->passages,
                'average_score' => $test->average_score ? (int) round((float) $test->average_score) : 0,
            ])
            ->all();
    }

    /**
     * Retourne les dernieres actions admin, ou une activite par defaut.
     */
    private function recentActivity(): array
    {
        if ($this->tableHasColumns('logs_admin', ['action', 'created_at'])) {
            return DB::table('logs_admin')
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(['id', 'action', 'created_at'])
                ->map(fn ($log): array => [
                    'id' => $log->id,
                    'title' => $log->action,
                    'time' => $log->created_at,
                    'tone' => 'blue',
                ])
                ->all();
        }

        return [
            ['id' => 1, 'title' => 'Tableau de bord administrateur initialise', 'time' => now()->toDateTimeString(), 'tone' => 'green'],
        ];
    }

    /**
     * Prepare la repartition des utilisateurs pour l'affichage graphique.
     */
    private function userDistribution(): array
    {
        $students = $this->countUsersByRole('etudiant');
        $counselors = $this->countUsersByRole('conseiller');
        $admins = $this->countUsersByRole('admin');
        $total = max($students + $counselors + $admins, 1);

        return [
            ['label' => 'Etudiants', 'value' => $students, 'percentage' => round(($students / $total) * 100, 1), 'color' => 'bg-blue-600'],
            ['label' => 'Conseillers', 'value' => $counselors, 'percentage' => round(($counselors / $total) * 100, 1), 'color' => 'bg-emerald-600'],
            ['label' => 'Admins', 'value' => $admins, 'percentage' => round(($admins / $total) * 100, 1), 'color' => 'bg-orange-500'],
        ];
    }

    /**
     * Alertes systeme synthetiques en attendant un vrai moteur de monitoring.
     */
    private function systemAlerts(): array
    {
        return [
            ['title' => 'Sauvegarde de la base de donnees reussie', 'detail' => now()->format('d/m/Y H:i'), 'tone' => 'green'],
            ['title' => 'Verification securite active', 'detail' => 'Surveillance des connexions', 'tone' => 'orange'],
            ['title' => 'Version plateforme disponible', 'detail' => 'University Key Admin', 'tone' => 'blue'],
        ];
    }

    /**
     * Evite les erreurs SQL quand une base locale n'a pas encore toutes ses colonnes.
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
