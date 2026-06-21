<?php

namespace App\Http\Controllers\Api\V1\Counselor;

use App\Http\Controllers\Controller;
use App\Services\Orientation\GeneralTestComparisonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class CounselorDashboardController extends Controller
{
    /**
     * Retourne le tableau de bord d'un conseiller valide et actif.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing('profilConseiller');

        if (! $user->isConseiller()) {
            return response()->json([
                'message' => 'Ce tableau de bord est reserve aux conseillers.',
            ], 403);
        }

        return response()->json([
            'data' => [
                'counselor' => $this->counselor($user),
                'metrics' => [
                    'students_followed' => $this->studentsFollowedCount($user->id),
                    'tests_reviewed' => $this->testsReviewedCount($user->id),
                    'unread_messages' => $this->unreadMessagesCount($user->id),
                    'positive_reviews' => $this->positiveReviews($user->id),
                    'rating' => $this->rating($user),
                ],
                'recent_tests' => $this->recentTests($user->id),
                'messages' => $this->recentMessages($user->id),
                'appointments' => $this->upcomingAppointments($user->id),
                'created_tests' => $this->createdTests($user->id),
                'orientation_alerts' => $this->orientationAlerts($user->id),
            ],
        ]);
    }

    /**
     * Formate l'identite professionnelle du conseiller.
     */
    private function counselor($user): array
    {
        $profile = $user->profilConseiller;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'first_name' => $profile?->prenom ?: str($user->name)->before(' ')->toString(),
            'specialty' => $profile?->specialite ?: 'Conseiller d\'orientation',
            'photo_url' => $this->publicPhotoUrl($profile?->photo),
            'is_available' => (bool) ($profile?->disponible ?? true),
        ];
    }

    /**
     * Compte les etudiants distincts qui ont une conversation avec ce conseiller.
     */
    private function studentsFollowedCount(int $counselorId): int
    {
        if (! $this->tableHasColumns('conversations', ['conseiller_id', 'etudiant_id'])) {
            return 0;
        }

        return DB::table('conversations')
            ->where('conseiller_id', $counselorId)
            ->distinct('etudiant_id')
            ->count('etudiant_id');
    }

    /**
     * Compte les tests termines par les etudiants suivis par ce conseiller.
     */
    private function testsReviewedCount(int $counselorId): int
    {
        if (! $this->tableHasColumns('conversations', ['conseiller_id', 'etudiant_id'])
            || ! $this->tableHasColumns('sessions_test', ['user_id', 'statut'])) {
            return 0;
        }

        return DB::table('sessions_test')
            ->where('statut', 'termine')
            ->whereIn('user_id', $this->studentIdsSubquery($counselorId))
            ->count();
    }

    /**
     * Compte les messages non lus envoyes par les etudiants.
     */
    private function unreadMessagesCount(int $counselorId): int
    {
        if (! $this->tableHasColumns('conversations', ['id', 'conseiller_id'])
            || ! $this->tableHasColumns('messages', ['conversation_id', 'expediteur_id', 'lu_le'])) {
            return 0;
        }

        return DB::table('messages')
            ->join('conversations', 'conversations.id', '=', 'messages.conversation_id')
            ->where('conversations.conseiller_id', $counselorId)
            ->where('messages.expediteur_id', '!=', $counselorId)
            ->whereNull('messages.lu_le')
            ->count();
    }

    /**
     * Calcule le nombre d'avis positifs publies.
     */
    private function positiveReviews(int $counselorId): int
    {
        if (! $this->tableHasColumns('evaluations_conseillers', ['conseiller_id', 'note', 'statut'])) {
            return 0;
        }

        return DB::table('evaluations_conseillers')
            ->where('conseiller_id', $counselorId)
            ->where('statut', 'publie')
            ->where('note', '>=', 4)
            ->count();
    }

    /**
     * Retourne la note moyenne et le volume d'avis.
     */
    private function rating($user): array
    {
        $profile = $user->profilConseiller;

        if ($this->tableHasColumns('evaluations_conseillers', ['conseiller_id', 'note', 'statut'])) {
            $query = DB::table('evaluations_conseillers')
                ->where('conseiller_id', $user->id)
                ->where('statut', 'publie');

            return [
                'average' => round((float) $query->avg('note'), 1),
                'count' => $query->count(),
            ];
        }

        return [
            'average' => (float) ($profile?->note_moyenne ?? 0),
            'count' => 0,
        ];
    }

    /**
     * Retourne les derniers tests des etudiants suivis.
     */
    private function recentTests(int $counselorId): array
    {
        if (! $this->tableHasColumns('conversations', ['conseiller_id', 'etudiant_id'])
            || ! $this->tableHasColumns('sessions_test', ['id', 'user_id', 'test_orientation_id', 'statut', 'score_global', 'termine_le'])
            || ! $this->tableHasColumns('tests_orientation', ['id', 'titre'])
            || ! $this->tableHasColumns('users', ['id', 'name'])) {
            return [];
        }

        return DB::table('sessions_test')
            ->join('users', 'users.id', '=', 'sessions_test.user_id')
            ->join('tests_orientation', 'tests_orientation.id', '=', 'sessions_test.test_orientation_id')
            ->where('sessions_test.statut', 'termine')
            ->whereIn('sessions_test.user_id', $this->studentIdsSubquery($counselorId))
            ->orderByDesc('sessions_test.termine_le')
            ->limit(5)
            ->get([
                'sessions_test.id',
                'users.name as student',
                'tests_orientation.titre as test',
                'sessions_test.score_global',
                'sessions_test.termine_le',
            ])
            ->map(fn ($session): array => [
                'id' => $session->id,
                'student' => $session->student,
                'test' => $session->test,
                'date' => $session->termine_le,
                'score' => $session->score_global ? (int) round((float) $session->score_global) : 0,
            ])
            ->all();
    }

    /**
     * Retourne les derniers messages recus.
     */
    private function recentMessages(int $counselorId): array
    {
        if (! $this->tableHasColumns('conversations', ['id', 'conseiller_id'])
            || ! $this->tableHasColumns('messages', ['id', 'conversation_id', 'expediteur_id', 'contenu', 'created_at', 'lu_le'])
            || ! $this->tableHasColumns('users', ['id', 'name'])) {
            return [];
        }

        return DB::table('messages')
            ->join('conversations', 'conversations.id', '=', 'messages.conversation_id')
            ->join('users', 'users.id', '=', 'messages.expediteur_id')
            ->where('conversations.conseiller_id', $counselorId)
            ->where('messages.expediteur_id', '!=', $counselorId)
            ->orderByDesc('messages.created_at')
            ->limit(5)
            ->get(['messages.id', 'messages.contenu', 'messages.created_at', 'messages.lu_le', 'users.name'])
            ->map(fn ($message): array => [
                'id' => $message->id,
                'sender' => $message->name,
                'excerpt' => str($message->contenu)->limit(70)->toString(),
                'received_at' => $message->created_at,
                'is_unread' => $message->lu_le === null,
            ])
            ->all();
    }

    /**
     * Rendez-vous de demonstration en attendant une table de planning dediee.
     */
    private function upcomingAppointments(int $counselorId): array
    {
        if (! $this->tableHasColumns('conversations', ['conseiller_id', 'etudiant_id'])
            || ! $this->tableHasColumns('users', ['id', 'name'])) {
            return [];
        }

        return DB::table('conversations')
            ->join('users', 'users.id', '=', 'conversations.etudiant_id')
            ->where('conversations.conseiller_id', $counselorId)
            ->orderByDesc('conversations.dernier_message_le')
            ->limit(4)
            ->get(['conversations.id', 'users.name'])
            ->values()
            ->map(fn ($conversation, int $index): array => [
                'id' => $conversation->id,
                'student' => $conversation->name,
                'day' => now()->addDays($index + 1)->format('d'),
                'month' => now()->addDays($index + 1)->locale('fr')->isoFormat('MMM'),
                'time' => sprintf('%02d:00 - %02d:30', 10 + $index, 10 + $index),
                'status' => $index === 2 ? 'en_attente' : 'confirme',
            ])
            ->all();
    }

    /**
     * Tests crees par le conseiller connecte.
     */
    private function createdTests(int $counselorId): array
    {
        if (! $this->tableHasColumns('tests_orientation', ['id', 'titre', 'cree_par'])
            || ! $this->tableHasColumns('questions', ['test_orientation_id'])) {
            return [];
        }

        return DB::table('tests_orientation')
            ->leftJoin('questions', 'questions.test_orientation_id', '=', 'tests_orientation.id')
            ->where('tests_orientation.cree_par', $counselorId)
            ->groupBy('tests_orientation.id', 'tests_orientation.titre')
            ->limit(4)
            ->get(['tests_orientation.id', 'tests_orientation.titre', DB::raw('count(questions.id) as questions_count')])
            ->map(fn ($test): array => [
                'id' => $test->id,
                'title' => $test->titre,
                'questions' => (int) $test->questions_count,
                'used' => 0,
            ])
            ->all();
    }

    /**
     * Signale au conseiller les grands ecarts entre Test general 1 et Test general 2.
     */
    private function orientationAlerts(int $counselorId): array
    {
        if (! $this->tableHasColumns('conversations', ['conseiller_id', 'etudiant_id'])
            || ! $this->tableHasColumns('users', ['id', 'name'])) {
            return [];
        }

        $comparisonService = app(GeneralTestComparisonService::class);

        return DB::table('conversations')
            ->join('users', 'users.id', '=', 'conversations.etudiant_id')
            ->where('conversations.conseiller_id', $counselorId)
            ->select('users.id', 'users.name')
            ->distinct()
            ->limit(10)
            ->get()
            ->flatMap(function ($student) use ($comparisonService) {
                $comparison = $comparisonService->compareForStudent((int) $student->id);

                return collect($comparison['alerts'])->map(fn (array $alert): array => [
                    'student_id' => $student->id,
                    'student' => $student->name,
                    ...$alert,
                ]);
            })
            ->values()
            ->all();
    }

    /**
     * Sous-requete des etudiants lies a ce conseiller.
     */
    private function studentIdsSubquery(int $counselorId)
    {
        return DB::table('conversations')
            ->select('etudiant_id')
            ->where('conseiller_id', $counselorId);
    }

    /**
     * Convertit un chemin de stockage en URL publique.
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

    /**
     * Protege les requetes contre les schemas locaux incomplets.
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
