<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultAdminSeeder extends Seeder
{
    /**
     * Cree le super administrateur par defaut demande pour la plateforme.
     */
    public function run(): void
    {
        // L'email interne garantit l'unicite; le nom MINESEC sert d'identifiant de connexion.
        User::query()->updateOrCreate(
            ['email' => 'minesec@university-key.local'],
            [
                'name' => 'MINESEC',
                'email' => 'minesec@university-key.local',
                'password' => Hash::make('Minesec@12345'),
                'role' => 'admin',
                'statut' => 'actif',
                'telephone' => null,
                'langue_preferee' => 'fr',
                'email_verified_at' => now(),
            ],
        );
    }
}
