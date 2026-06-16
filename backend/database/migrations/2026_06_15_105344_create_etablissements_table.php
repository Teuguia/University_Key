<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catalogue des établissements d'enseignement supérieur au Cameroun.
     * Géré uniquement par l'administrateur.
     */
    public function up(): void
    {
        Schema::create('etablissements', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->enum('type', [
                'universite_publique',
                'universite_privee',
                'institut',
                'centre_formation',
                'ecole_professionnelle'
            ]);
            $table->string('ville');
            $table->enum('region', [ 'Adamaoua', 'Centre', 'Est', 'Extrême-Nord', 'Littoral', 'Nord', 'Nord-Ouest', 'Ouest', 'Sud', 'Sud-Ouest'
            ]);
            $table->string('adresse')->nullable();
            $table->string('telephone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('site_web')->nullable();
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            // Frais en FCFA
            $table->integer('frais_min')->nullable();
            $table->integer('frais_max')->nullable();
            $table->text('conditions_admission')->nullable();
            $table->boolean('a_concours')->default(false);
            $table->text('details_concours')->nullable();
             // Statut de validation par l'administrateur
             // Un établissement doit être validé pour être visible sur la plateforme
             $table->enum('statut', ['en_attente', 'valide', 'rejete'])->default('en_attente');
             // Permet à l'administrateur de masquer un établissement sans le supprimer
             // Utile pour les établissements obsolètes ou en maintenance
            // Visible ou non sur la plateforme
            $table->boolean('valide')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etablissements');
    }
};