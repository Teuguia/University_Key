<?php

// Commentaire d'intention: porte les informations detaillees du profil conseiller.

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'prenom',
    'nom',
    'ville',
    'region',
    'specialite',
    'annees_experience',
    'biographie',
    'diplomes',
    'cv_path',
    'documents_path',
    'disponible',
    'disponibilite_details',
    'note_moyenne',
    'nb_etudiants_accompagnes',
    'photo',
])]
class ProfilConseiller extends Model
{
    use HasFactory;

    /**
     * Nom exact de la table definie dans les migrations.
     */
    protected $table = 'profils_conseillers';

    /**
     * Retourne le compte utilisateur auquel appartient ce profil conseiller.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Convertit les champs JSON, booleens et nombres dans leurs types PHP naturels.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'diplomes' => 'array',
            'documents_path' => 'array',
            'disponible' => 'boolean',
            'note_moyenne' => 'decimal:2',
        ];
    }
}
