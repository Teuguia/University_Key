<?php

// Commentaire d'intention: suit la validation administrative des comptes conseillers.

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'conseiller_id',
    'statut',
    'diplome_principal',
    'etablissement_diplome',
    'annees_experience',
    'description_experience',
    'specialite',
    'motif_rejet',
    'commentaire_admin',
    'traite_par',
    'traite_le',
    'tentative',
])]
class ValidationConseiller extends Model
{
    use HasFactory;

    protected $table = 'validations_conseillers';

    /**
     * Retourne le compte conseiller concerne par cette demande.
     */
    public function conseiller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'conseiller_id');
    }

    /**
     * Retourne l'administrateur qui a traite la demande.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'traite_par');
    }

    /**
     * Convertit les champs de suivi en types PHP utiles.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'annees_experience' => 'integer',
            'traite_le' => 'datetime',
        ];
    }
}
