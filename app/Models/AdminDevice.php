<?php

// Commentaire d'intention: represente un navigateur/appareil autorise pour un compte administrateur.

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'device_id_hash',
    'device_name',
    'user_agent',
    'last_ip',
    'authorized_at',
    'last_used_at',
])]
class AdminDevice extends Model
{
    use HasFactory;

    /**
     * Retourne le compte administrateur auquel appartient cet appareil.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Definit les dates manipulees automatiquement par Eloquent.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'authorized_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }
}
