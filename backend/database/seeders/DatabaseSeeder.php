<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Textes legaux publics necessaires aux modales conditions/confidentialite.
        $this->call(RegleSeeder::class);
        // Compte de supervision disponible des l'installation locale.
        $this->call(DefaultAdminSeeder::class);
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

        // User::factory(10)->create();

        User::query()->updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
            ],
        );
    }
}
