<?php

namespace App\Services\Orientation;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GeneralTestComparisonService
{
    /**
     * Ecart en points a partir duquel le conseiller doit etre alerte.
     */
    private const SIGNIFICANT_GAP = 30;

    /**
     * Titres exacts des deux tests generaux a croiser.
     */
    private const TEST_TITLES = [
        'direct' => 'Test general',
        'situation' => 'Test general 2 - Mises en situation',
    ];

    /**
     * Compare les derniers resultats des deux tests generaux d'un etudiant.
     */
    public function compareForStudent(int $studentId): array
    {
        if (! $this->hasRequiredTables()) {
            return $this->emptyResult();
        }

        $sessions = $this->latestGeneralSessions($studentId);

        if (! isset($sessions['direct'], $sessions['situation'])) {
            return [
                ...$this->emptyResult(),
                'status' => 'incomplete',
                'missing_tests' => array_values(array_diff(array_keys(self::TEST_TITLES), array_keys($sessions))),
            ];
        }

        $directScores = $this->scoresByFiliere((int) $sessions['direct']->id);
        $situationScores = $this->scoresByFiliere((int) $sessions['situation']->id);
        $combined = [];
        $alerts = [];

        foreach ($this->knownSlugs() as $slug => $label) {
            $test1 = $directScores[$slug] ?? 0;
            $test2 = $situationScores[$slug] ?? 0;
            $gap = abs($test1 - $test2);

            // Moyenne ponderee demandee: 50% preferences directes, 50% reactions en situation.
            $combined[$slug] = [
                'label' => $label,
                'test_1_score' => round($test1, 2),
                'test_2_score' => round($test2, 2),
                'combined_score' => round(($test1 * 0.5) + ($test2 * 0.5), 2),
                'gap' => round($gap, 2),
            ];

            if ($gap >= self::SIGNIFICANT_GAP) {
                $alerts[] = [
                    'filiere' => $slug,
                    'label' => $label,
                    'test_1_score' => round($test1, 2),
                    'test_2_score' => round($test2, 2),
                    'gap' => round($gap, 2),
                    'message' => "Grand ecart detecte pour {$label}: preference declaree et reaction concrete ne sont pas alignees.",
                ];
            }
        }

        return [
            'status' => 'ready',
            'threshold' => self::SIGNIFICANT_GAP,
            'combined_scores' => $combined,
            'alerts' => $alerts,
            'has_alerts' => count($alerts) > 0,
        ];
    }

    /**
     * Retourne les scores par filiere pour une session terminee.
     */
    private function scoresByFiliere(int $sessionId): array
    {
        $rows = DB::table('recommandations')
            ->join('filieres', 'filieres.id', '=', 'recommandations.filiere_id')
            ->where('recommandations.session_test_id', $sessionId)
            ->get(['filieres.nom', 'recommandations.score']);

        $scores = [];

        foreach ($rows as $row) {
            $slug = $this->slugForFiliere($row->nom);

            if ($slug) {
                $scores[$slug] = (float) $row->score;
            }
        }

        return $scores;
    }

    /**
     * Recupere la derniere session terminee de chaque test general.
     */
    private function latestGeneralSessions(int $studentId): array
    {
        $rows = DB::table('sessions_test')
            ->join('tests_orientation', 'tests_orientation.id', '=', 'sessions_test.test_orientation_id')
            ->where('sessions_test.user_id', $studentId)
            ->where('sessions_test.statut', 'termine')
            ->whereIn('tests_orientation.titre', array_values(self::TEST_TITLES))
            ->orderByDesc('sessions_test.termine_le')
            ->orderByDesc('sessions_test.updated_at')
            ->get([
                'sessions_test.id',
                'tests_orientation.titre',
            ]);

        $sessions = [];

        foreach ($rows as $row) {
            $key = array_search($row->titre, self::TEST_TITLES, true);

            if ($key && ! isset($sessions[$key])) {
                $sessions[$key] = $row;
            }
        }

        return $sessions;
    }

    /**
     * Associe les noms de filieres aux codes utilises dans les questionnaires.
     */
    private function slugForFiliere(string $name): ?string
    {
        return array_flip($this->knownSlugs())[$name] ?? null;
    }

    /**
     * Codes de filieres connus par les deux tests generaux.
     */
    private function knownSlugs(): array
    {
        return [
            'sci' => 'Scientifique',
            'tech' => 'Technique',
            'com' => 'Commercial',
            'sante' => 'Sante',
            'lit' => 'Litteraire',
        ];
    }

    /**
     * Evite les erreurs tant que les migrations du moteur de test ne sont pas disponibles.
     */
    private function hasRequiredTables(): bool
    {
        $requirements = [
            'sessions_test' => ['id', 'user_id', 'test_orientation_id', 'statut'],
            'tests_orientation' => ['id', 'titre'],
            'recommandations' => ['session_test_id', 'filiere_id', 'score'],
            'filieres' => ['id', 'nom'],
        ];

        foreach ($requirements as $table => $columns) {
            if (! Schema::hasTable($table)) {
                return false;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Format stable quand la comparaison n'est pas encore possible.
     */
    private function emptyResult(): array
    {
        return [
            'status' => 'unavailable',
            'threshold' => self::SIGNIFICANT_GAP,
            'combined_scores' => [],
            'alerts' => [],
            'has_alerts' => false,
        ];
    }
}
