<?php

// Commentaire d'intention: modele central des comptes et point d'entree des relations utilisateur.

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'name',
    'email',
    'password',
    'role',
    'statut',
    'telephone',
    'langue_preferee',
    'derniere_connexion',
    'email_verified_at',
    'telephone_verified_at',
    'verification_requise',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Retourne le profil etudiant associe au compte.
     */
    public function profilEtudiant(): HasOne
    {
        return $this->hasOne(ProfilEtudiant::class);
    }

    /**
     * Retourne le profil conseiller associe au compte.
     */
    public function profilConseiller(): HasOne
    {
        return $this->hasOne(ProfilConseiller::class);
    }

    /**
     * Retourne les demandes de validation soumises par ce conseiller.
     */
    public function validationsConseiller(): HasMany
    {
        return $this->hasMany(ValidationConseiller::class, 'conseiller_id');
    }

    /**
     * Retourne les codes de verification email/telephone du compte.
     */
    public function codesVerification(): HasMany
    {
        return $this->hasMany(CodeVerification::class);
    }

    /**
     * Retourne les sessions de test d'orientation de l'utilisateur.
     */
    public function sessionsTest(): HasMany
    {
        return $this->hasMany(SessionTest::class);
    }

    /**
     * Retourne les recommandations calculees pour l'utilisateur.
     */
    public function recommandations(): HasMany
    {
        return $this->hasMany(Recommandation::class);
    }

    /**
     * Retourne les favoris sauvegardes par l'utilisateur.
     */
    public function favoris(): HasMany
    {
        return $this->hasMany(Favori::class);
    }

    /**
     * Indique si l'utilisateur possede le role administrateur.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Indique si l'utilisateur possede le role conseiller.
     */
    public function isConseiller(): bool
    {
        return $this->role === 'conseiller';
    }

    /**
     * Indique si l'utilisateur possede le role etudiant.
     */
    public function isEtudiant(): bool
    {
        return $this->role === 'etudiant';
    }

    /**
     * Definit les conversions automatiques appliquees aux attributs du modele.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'telephone_verified_at' => 'datetime',
            'verification_requise' => 'boolean',
            'password' => 'hashed',
            'derniere_connexion' => 'datetime',
        ];
    }
}
