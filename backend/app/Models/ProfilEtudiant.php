<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'prenom',
    'nom',
    'date_naissance',
    'sexe',
    'ville',
    'region',
    'type_bac',
    'annee_bac',
    'moyenne_generale',
    'matieres_fortes',
    'matieres_faibles',
    'budget_annuel',
    'centres_interet',
    'objectif_professionnel',
    'preference_public',
    'mobilite',
    'photo',
])]
class ProfilEtudiant extends Model
{
    use HasFactory;

    /**
     * Nom exact de la table definie dans les migrations.
     */
    protected $table = 'profils_etudiants';

    /**
     * Retourne le compte utilisateur auquel appartient ce profil.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Convertit les champs JSON et booleens dans leurs types PHP naturels.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_naissance' => 'date',
            'matieres_fortes' => 'array',
            'matieres_faibles' => 'array',
            'centres_interet' => 'array',
            'preference_public' => 'boolean',
            'mobilite' => 'boolean',
        ];
    }
}
