<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'code',
    'type',
    'cible',
    'statut',
    'expire_le',
    'nb_tentatives',
])]
class CodeVerification extends Model
{
    use HasFactory;

    /**
     * Nom exact de la table definie dans les migrations.
     */
    protected $table = 'codes_verification';

    /**
     * Retourne le compte lie au code OTP.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Convertit l'expiration en objet date Laravel.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expire_le' => 'datetime',
        ];
    }
}
