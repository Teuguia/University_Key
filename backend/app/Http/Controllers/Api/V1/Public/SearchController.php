<?php

// Commentaire d'intention: fournit la recherche publique d'ecoles et de filieres.

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SearchController extends Controller
{
    /**
     * Recherche publique unique: etablissements directs + etablissements lies aux filieres trouvees.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if (mb_strlen($query) < 2 || ! $this->hasSearchTables()) {
            return response()->json(['data' => []]);
        }

        $directMatches = $this->searchEtablissements($query);
        $filiereMatches = $this->searchEtablissementsByFiliere($query);
        $results = [];

        foreach ([...$directMatches, ...$filiereMatches] as $row) {
            $id = (int) $row['id'];

            if (! isset($results[$id])) {
                $results[$id] = $row;
                continue;
            }

            $results[$id]['matched_filieres'] = array_values(array_unique([
                ...$results[$id]['matched_filieres'],
                ...$row['matched_filieres'],
            ]));
        }

        return response()->json([
            'data' => array_slice(array_values($results), 0, 8),
        ]);
    }

    /**
     * Recherche par nom, ville, region ou description d'etablissement.
     */
    private function searchEtablissements(string $query): array
    {
        $like = '%' . mb_strtolower($query) . '%';

        return DB::table('etablissements')
            ->where(fn ($builder) => $this->applyVisibilityFilter($builder))
            ->where(function ($builder) use ($like): void {
                $builder->whereRaw('lower(nom) like ?', [$like])
                    ->orWhereRaw('lower(ville) like ?', [$like]);

                if (Schema::hasColumn('etablissements', 'region')) {
                    $builder->orWhereRaw('lower(region) like ?', [$like]);
                }

                if (Schema::hasColumn('etablissements', 'description')) {
                    $builder->orWhereRaw('lower(description) like ?', [$like]);
                }
            })
            ->orderBy('nom')
            ->limit(8)
            ->get(['id', 'nom', 'ville', 'region', 'type'])
            ->map(fn ($row): array => $this->formatResult($row, 'etablissement', []))
            ->all();
    }

    /**
     * Recherche les filieres puis remonte les etablissements qui les proposent.
     */
    private function searchEtablissementsByFiliere(string $query): array
    {
        if (! Schema::hasTable('etablissement_filiere')) {
            return [];
        }

        $like = '%' . mb_strtolower($query) . '%';

        return DB::table('etablissements')
            ->join('etablissement_filiere', 'etablissement_filiere.etablissement_id', '=', 'etablissements.id')
            ->join('filieres', 'filieres.id', '=', 'etablissement_filiere.filiere_id')
            ->where(fn ($builder) => $this->applyVisibilityFilter($builder, 'etablissements.'))
            ->where(function ($builder) use ($like): void {
                $builder->whereRaw('lower(filieres.nom) like ?', [$like])
                    ->orWhereRaw('lower(filieres.domaine) like ?', [$like]);

                if (Schema::hasColumn('filieres', 'description')) {
                    $builder->orWhereRaw('lower(filieres.description) like ?', [$like]);
                }
            })
            ->select([
                'etablissements.id',
                'etablissements.nom',
                'etablissements.ville',
                'etablissements.region',
                'etablissements.type',
                DB::raw("string_agg(distinct filieres.nom, ', ') as matched_filieres"),
            ])
            ->groupBy('etablissements.id', 'etablissements.nom', 'etablissements.ville', 'etablissements.region', 'etablissements.type')
            ->orderBy('etablissements.nom')
            ->limit(8)
            ->get()
            ->map(function ($row): array {
                $filieres = $row->matched_filieres
                    ? array_map(fn ($name): string => $this->displayFiliereName($name), explode(', ', $row->matched_filieres))
                    : [];

                return $this->formatResult($row, 'filiere', $filieres);
            })
            ->all();
    }

    /**
     * Formate une suggestion lisible par le dropdown React.
     */
    private function formatResult($row, string $matchType, array $filieres): array
    {
        return [
            'id' => (int) $row->id,
            'type' => 'etablissement',
            'match_type' => $matchType,
            'title' => $row->nom,
            'subtitle' => trim(($row->ville ?? '') . ' · ' . ($filieres[0] ?? $this->typeLabel($row->type ?? ''))),
            'city' => $row->ville ?? null,
            'region' => $row->region ?? null,
            'matched_filieres' => $filieres,
            'url' => '#etablissement-' . $row->id,
        ];
    }

    /**
     * Traduit les types techniques en libelles courts.
     */
    private function typeLabel(?string $type): string
    {
        return [
            'universite_publique' => 'Universite publique',
            'universite_privee' => 'Universite privee',
            'institut' => 'Institut',
            'centre_formation' => 'Centre de formation',
            'ecole_professionnelle' => 'Ecole professionnelle',
        ][$type] ?? 'Etablissement';
    }

    /**
     * Masque le prefixe technique "CODE - " avant affichage dans les suggestions.
     */
    private function displayFiliereName(string $name): string
    {
        return preg_replace('/^.+? - /', '', $name) ?: $name;
    }

    /**
     * Garde la recherche publique limitee aux etablissements visibles.
     */
    private function applyVisibilityFilter($builder, string $prefix = '')
    {
        $hasStatus = Schema::hasColumn('etablissements', 'statut');
        $hasValide = Schema::hasColumn('etablissements', 'valide');

        if ($hasStatus) {
            return $builder->where($prefix . 'statut', 'valide');
        }

        if ($hasValide) {
            return $builder->where($prefix . 'valide', true);
        }

        return $builder;
    }

    /**
     * Evite les erreurs si une base locale n'a pas encore toutes les migrations.
     */
    private function hasSearchTables(): bool
    {
        foreach (['etablissements', 'filieres'] as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        foreach (['id', 'nom', 'ville'] as $column) {
            if (! Schema::hasColumn('etablissements', $column)) {
                return false;
            }
        }

        return Schema::hasColumn('filieres', 'nom');
    }
}
