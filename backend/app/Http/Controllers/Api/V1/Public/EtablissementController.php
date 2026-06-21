<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EtablissementController extends Controller
{
    /**
     * Affiche la fiche publique d'un etablissement avec ses filieres.
     */
    public function show(int $etablissement): JsonResponse
    {
        if (! Schema::hasTable('etablissements')) {
            return response()->json(['message' => 'Catalogue indisponible.'], 404);
        }

        $row = DB::table('etablissements')
            ->where('id', $etablissement)
            ->where(fn ($builder) => $this->applyVisibilityFilter($builder))
            ->first();

        if (! $row) {
            return response()->json(['message' => 'Etablissement introuvable.'], 404);
        }

        $photoPaths = $this->photoPaths($row);

        return response()->json([
            'data' => [
                'id' => (int) $row->id,
                'name' => $this->displayFiliereName($row->nom),
                'type' => $row->type ?? null,
                'type_label' => $this->typeLabel($row->type ?? null),
                'city' => $row->ville ?? null,
                'region' => $row->region ?? null,
                'address' => $row->adresse ?? null,
                'phone' => $row->telephone ?? null,
                'email' => $row->email ?? null,
                'website' => $row->site_web ?? null,
                'logo_url' => $this->publicAssetUrl($row->logo ?? null),
                'description' => $row->description ?? '',
                'fees' => [
                    'min' => $row->frais_min ?? null,
                    'max' => $row->frais_max ?? null,
                ],
                'admission' => $row->conditions_admission ?? null,
                'has_competition' => (bool) ($row->a_concours ?? false),
                'competition_details' => $row->details_concours ?? null,
                'photos' => array_values(array_unique(array_filter(array_map(
                    fn (string $path): ?string => $this->publicAssetUrl($path),
                    $photoPaths
                )))),
                'filieres' => $this->filieresForEtablissement((int) $row->id),
            ],
        ]);
    }

    /**
     * Liste les filieres rattachees a l'etablissement via le pivot.
     */
    private function filieresForEtablissement(int $etablissementId): array
    {
        if (! Schema::hasTable('etablissement_filiere') || ! Schema::hasTable('filieres')) {
            return [];
        }

        return DB::table('filieres')
            ->join('etablissement_filiere', 'etablissement_filiere.filiere_id', '=', 'filieres.id')
            ->where('etablissement_filiere.etablissement_id', $etablissementId)
            ->when(Schema::hasColumn('filieres', 'active'), fn ($builder) => $builder->where('filieres.active', true))
            ->orderBy('filieres.nom')
            ->get([
                'filieres.id',
                'filieres.nom',
                'filieres.domaine',
                'filieres.niveau',
                'filieres.duree_annees',
                'filieres.diplome_obtenu',
                'filieres.conditions_acces',
                'filieres.debouches',
                'etablissement_filiere.frais_specifiques',
            ])
            ->map(fn ($row): array => [
                'id' => (int) $row->id,
                'name' => $row->nom,
                'domain' => $row->domaine,
                'level' => $row->niveau,
                'duration_years' => $row->duree_annees ? (int) $row->duree_annees : null,
                'degree' => $row->diplome_obtenu,
                'admission' => $row->conditions_acces,
                'opportunities' => $row->debouches,
                'specific_fees' => $row->frais_specifiques ? (int) $row->frais_specifiques : null,
            ])
            ->all();
    }

    /**
     * Convertit un chemin storage en URL publique utilisable par React.
     */
    private function publicAssetUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str($path)->startsWith(['http://', 'https://', '/'])) {
            return $path;
        }

        if (str($path)->startsWith('images/')) {
            return asset($path);
        }

        return asset('storage/' . ltrim($path, '/'));
    }

    /**
     * Assemble la photo principale et la galerie JSON, en restant compatible
     * avec les etablissements ne possedant qu'un logo.
     */
    private function photoPaths(object $school): array
    {
        $gallery = [];

        if (Schema::hasColumn('etablissements', 'photos') && ! empty($school->photos)) {
            $decoded = is_string($school->photos) ? json_decode($school->photos, true) : $school->photos;
            $gallery = is_array($decoded) ? $decoded : [];
        }

        return array_values(array_filter([
            $school->logo ?? null,
            ...$gallery,
        ]));
    }

    /**
     * Masque le prefixe technique "CODE - " avant affichage public.
     */
    private function displayFiliereName(string $name): string
    {
        return preg_replace('/^.+? - /', '', $name) ?: $name;
    }

    /**
     * Meme filtre public que la recherche universelle.
     */
    private function applyVisibilityFilter($builder)
    {
        $hasStatus = Schema::hasColumn('etablissements', 'statut');
        $hasValide = Schema::hasColumn('etablissements', 'valide');

        if ($hasStatus) {
            return $builder->where('statut', 'valide');
        }

        if ($hasValide) {
            return $builder->where('valide', true);
        }

        return $builder;
    }

    /**
     * Traduit le type technique en libelle utilisateur.
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
}
