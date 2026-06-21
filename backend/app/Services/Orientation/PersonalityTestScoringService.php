<?php

namespace App\Services\Orientation;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PersonalityTestScoringService
{
    /**
     * Axes psychologiques calcules avant conversion vers les filieres.
     */
    private const AXES = ['log', 'soc', 'crea', 'lead'];

    /**
     * Table fixe de correspondance entre traits de personnalite et filieres.
     */
    private const CORRESPONDENCE = [
        'sci' => ['log' => 0.6, 'soc' => 0.0, 'crea' => 0.1, 'lead' => 0.3],
        'tech' => ['log' => 0.5, 'soc' => 0.0, 'crea' => 0.3, 'lead' => 0.2],
        'lit' => ['log' => 0.1, 'soc' => 0.3, 'crea' => 0.5, 'lead' => 0.1],
        'com' => ['log' => 0.2, 'soc' => 0.1, 'crea' => 0.1, 'lead' => 0.6],
        'sante' => ['log' => 0.3, 'soc' => 0.6, 'crea' => 0.0, 'lead' => 0.1],
    ];

    /**
     * Calcule le profil personnalite puis les compatibilites filieres d'une session.
     */
    public function scoreSession(int $sessionId): array
    {
        if (! $this->hasRequiredTables()) {
            return $this->emptyResult('unavailable');
        }

        $answers = DB::table('reponses_etudiants')
            ->join('choix_reponses', 'choix_reponses.id', '=', 'reponses_etudiants.choix_reponse_id')
            ->where('reponses_etudiants.session_test_id', $sessionId)
            ->whereNotNull('reponses_etudiants.choix_reponse_id')
            ->get(['choix_reponses.metadata']);

        if ($answers->isEmpty()) {
            return $this->emptyResult('empty');
        }

        $axisTotals = array_fill_keys(self::AXES, 0.0);

        foreach ($answers as $answer) {
            $metadata = $answer->metadata ? json_decode($answer->metadata, true) : [];
            $axes = $metadata['axes'] ?? [];

            foreach (self::AXES as $axis) {
                $axisTotals[$axis] += (float) ($axes[$axis] ?? 0);
            }
        }

        $profile = $this->normalizeToPercent($axisTotals);

        return [
            'status' => 'ready',
            'personality_profile' => $profile,
            'filiere_scores' => $this->filieresFromProfile($profile),
            'correspondence' => self::CORRESPONDENCE,
        ];
    }

    /**
     * Ecrit les recommandations finales produites par le test de personnalite.
     */
    public function persistRecommendations(int $sessionId): array
    {
        $result = $this->scoreSession($sessionId);

        if (($result['status'] ?? null) !== 'ready') {
            return $result;
        }

        $session = DB::table('sessions_test')->where('id', $sessionId)->first(['id', 'user_id']);

        if (! $session) {
            return $this->emptyResult('session_not_found');
        }

        DB::transaction(function () use ($session, $result): void {
            DB::table('recommandations')->where('session_test_id', $session->id)->delete();

            $rank = 1;
            $scores = $result['filiere_scores'];
            arsort($scores);

            foreach ($scores as $slug => $score) {
                $filiereId = $this->filiereId($slug);

                if (! $filiereId) {
                    continue;
                }

                DB::table('recommandations')->insert([
                    'user_id' => $session->user_id,
                    'session_test_id' => $session->id,
                    'filiere_id' => $filiereId,
                    'score' => $score,
                    'explication' => "Score issu du profil personnalite: {$slug}.",
                    'rang' => $rank++,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        return $result;
    }

    /**
     * Convertit un profil en compatibilites filieres et normalise le total a 100.
     */
    public function filieresFromProfile(array $profile): array
    {
        $rawScores = [];

        foreach (self::CORRESPONDENCE as $filiere => $axisWeights) {
            $score = 0;

            foreach ($axisWeights as $axis => $coefficient) {
                $score += ((float) ($profile[$axis] ?? 0)) * $coefficient;
            }

            $rawScores[$filiere] = round($score, 1);
        }

        return $this->normalizeToPercent($rawScores);
    }

    /**
     * Normalise des scores positifs en pourcentages.
     */
    private function normalizeToPercent(array $scores): array
    {
        $total = array_sum($scores);

        if ($total <= 0) {
            return array_fill_keys(array_keys($scores), 0.0);
        }

        $normalized = [];

        foreach ($scores as $key => $score) {
            $normalized[$key] = round((((float) $score) / $total) * 100, 1);
        }

        return $normalized;
    }

    /**
     * Retrouve l'identifiant d'une filiere a partir de son code algorithme.
     */
    private function filiereId(string $slug): ?int
    {
        $names = [
            'sci' => 'Scientifique',
            'tech' => 'Technique',
            'lit' => 'Litteraire',
            'com' => 'Commercial',
            'sante' => 'Sante',
        ];

        $id = DB::table('filieres')->where('nom', $names[$slug] ?? null)->value('id');

        return $id ? (int) $id : null;
    }

    /**
     * Verifie les tables minimales avant calcul pour eviter une API fragile.
     */
    private function hasRequiredTables(): bool
    {
        $requirements = [
            'reponses_etudiants' => ['session_test_id', 'choix_reponse_id'],
            'choix_reponses' => ['id', 'metadata'],
            'sessions_test' => ['id', 'user_id'],
            'recommandations' => ['user_id', 'session_test_id', 'filiere_id', 'score', 'rang'],
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
     * Format de retour stable quand le calcul ne peut pas encore se faire.
     */
    private function emptyResult(string $status): array
    {
        return [
            'status' => $status,
            'personality_profile' => array_fill_keys(self::AXES, 0.0),
            'filiere_scores' => array_fill_keys(array_keys(self::CORRESPONDENCE), 0.0),
            'correspondence' => self::CORRESPONDENCE,
        ];
    }
}
