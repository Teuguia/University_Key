<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
      //Profil professionnel du conseiller. Séparé de users car les infos sont très différentes de celles d'un étudiant — spécialité, expérience, diplômes, etc. Ce profil n'est visible publiquement qu'une fois le conseiller validé.
    public function up(): void
    {
        Schema::create('profils_conseillers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->unique()
                  ->constrained('users')
                  ->onDelete('cascade');

            // Informations personnelles
            $table->string('prenom');
            $table->string('nom');
            $table->string('ville')->nullable();
            $table->enum('region', [ 'Adamaoua', 'Centre', 'Est', 'Extrême-Nord','Littoral', 'Nord', 'Nord-Ouest', 'Ouest', 'Sud', 'Sud-Ouest'
            ])->nullable();
            // Informations professionnelles
            $table->string('specialite'); // ex: "Orientation scolaire et professionnelle"
            $table->integer('annees_experience')->default(0);
            $table->text('biographie')->nullable(); // Présentation visible par les étudiants
            $table->json('diplomes')->nullable(); // ex: [{"titre":"Master","etablissement":"UYI","annee":2018}]

            // Documents justificatifs uploadés (CV, diplômes scannés)
            // On stocke le chemin du fichier PDF sur le serveur
            $table->string('cv_path')->nullable();
            $table->json('documents_path')->nullable(); // plusieurs fichiers possibles

            // Disponibilité — le conseiller indique ses créneaux
            $table->boolean('disponible')->default(true);
            $table->text('disponibilite_details')->nullable(); // ex: "Lundi-Vendredi 8h-17h"

            // Note moyenne calculée automatiquement à chaque évaluation
            // via un Observer Laravel — pas mis à jour manuellement
            $table->decimal('note_moyenne', 3, 2)->default(0.00); // ex: 4.75
            $table->integer('nb_etudiants_accompagnes')->default(0);

            // Photo de profil professionnelle
            $table->string('photo')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profils_conseillers');
    }
};
