<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     // Filières disponibles dans les établissements. Une filière peut être proposée par plusieurs établissements la relation se fera via une table pivot etablissement_filiere.
    public function up(): void
    {
        Schema::create('filieres', function (Blueprint $table) {
            $table->id();

            $table->string('nom');
            $table->string('domaine')->index(); // ex: Informatique, Medecine, Droit
            $table->text('description')->nullable();
            $table->enum('niveau', [
                'BTS', 'HND', 'Licence', 'Master', 'Doctorat', 'Formation_professionnelle', 'Classe_preparatoire'
            ]);
            $table->integer('duree_annees'); // durée en années
            $table->string('diplome_obtenu'); // ex: "Licence en Informatique"
            $table->text('conditions_acces')->nullable();
            $table->text('debouches')->nullable();
            $table->json('metiers_associes')->nullable();    // ["Développeur", "Analyste"]
            $table->json('competences_requises')->nullable(); // ["Logique", "Maths"]

            // Visible sur la plateforme
            $table->boolean('active')->default(true)->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('filieres');
    }
};
