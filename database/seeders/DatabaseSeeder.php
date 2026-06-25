<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Compte administrateur principal; le mot de passe reste dans l'environnement, pas dans Git.
        if ($adminPassword = env('ADMIN_PASSWORD')) {
            User::query()->updateOrCreate(
                ['name' => env('ADMIN_NAME', 'MINESEC1')],
                [
                    'email' => env('ADMIN_EMAIL', 'admin@universitykey.cm'),
                    'password' => Hash::make($adminPassword),
                    'role' => 'admin',
                    'statut' => 'actif',
                    'verification_requise' => false,
                    'email_verified_at' => now(),
                    'telephone_verified_at' => now(),
                    'telephone' => env('ADMIN_PHONE', '690232871'),
                    'langue_preferee' => 'fr',
                ]
            );
        }

        // Textes legaux publics necessaires aux modales conditions/confidentialite.
        $this->call(RegleSeeder::class);
        // Test general d'orientation: 20 questions et poids par grandes filieres.
        $this->call(GeneralOrientationTestSeeder::class);
        // Test general complementaire: mises en situation pour croiser les resultats.
        $this->call(GeneralSituationOrientationTestSeeder::class);
        // Test de personnalite: axes psychologiques convertis ensuite en compatibilites.
        $this->call(PersonalityOrientationTestSeeder::class);
        // Catalogue initial des etablissements camerounais et de leurs filieres.
        $this->call(CameroonInstitutionSeeder::class);
        // Photos de presentation verifiees pour les etablissements qui en publient une.
        $this->call(InstitutionImageSeeder::class);
    }
}
