<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
     // Un étudiant complète son profil après inscription.Cette table est séparée de users pour garder users légère et ne stocker ici que les infos académiques spécifiques.
    public function up(): void
    {
        Schema::create('profils_etudiants', function (Blueprint $table) {
            $table->id();

            // Lien vers le compte utilisateur — suppression en cascade :si l'user est supprimé, son profil l'est aussi automatiquement
            $table->foreignId('user_id')
                  ->unique()
                  ->constrained('users')
                  ->onDelete('cascade');

            // Informations personnelles
            $table->string('prenom');
            $table->string('nom');
            $table->date('date_naissance')->nullable();
            $table->enum('sexe', ['M', 'F', 'non_precise'])->nullable();
            $table->string('ville')->nullable();
            $table->enum('region', [
                'Adamaoua', 'Centre', 'Est', 'Extrême-Nord',
                'Littoral', 'Nord', 'Nord-Ouest', 'Ouest', 'Sud', 'Sud-Ouest'
            ])->nullable();
            $table->enum('type_bac', ['A', 'C', 'D', 'E', 'F1', 'F2', 'F3', 'F4',
                'TI', 'GCE_AL', 'GCE_OL', 'autre'  ])->nullable();
            $table->year('annee_bac')->nullable();
            $table->decimal('moyenne_generale', 4, 2)->nullable(); 
            $table->json('matieres_fortes')->nullable();  
            $table->json('matieres_faibles')->nullable();
            $table->enum('budget_annuel', ['55.000 FCFA', '500.000', '2.000.000'])->nullable(); // en FCFA
            $table->json('centres_interet')->nullable();  // ex: ["Informatique", "Médecine"]
            $table->text('objectif_professionnel')->nullable();
            $table->boolean('preference_public')->nullable(); //par défault public=true, false=privé 
            $table->boolean('mobilite')->default(false); // accepte de changer de ville ?
            $table->string('photo')->nullable(); // pas obiligatoire, peut être ajouté plus tard
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profils_etudiants');
    }
};
