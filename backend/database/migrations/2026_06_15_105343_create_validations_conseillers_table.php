<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cette table gère le processus de validation des conseillers.
     * Quand un conseiller s'inscrit, une demande est automatiquement
     * créée ici avec statut 'en_attente'.
     * L'admin consulte cette table dans son tableau de bord
     * pour approuver ou rejeter chaque conseiller.
     */
    public function up(): void
    {
        Schema::create('validations_conseillers', function (Blueprint $table) {
            $table->id();

            // Le conseiller qui soumet sa demande
            $table->foreignId('conseiller_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            // Statut de la demande — commence toujours à 'en_attente'
            $table->enum('statut', [
                'en_attente',   // vient de s'inscrire
                'en_cours',     // admin est en train d'examiner
                'approuve',     // validé → compte activé
                'rejete',       // refusé → email envoyé avec motif
                'suspendu'      // était validé mais suspendu après coup
            ])->default('en_attente');

            // Informations soumises par le conseiller pour examen
            $table->string('diplome_principal');       // ex: "Master en Psychologie"
            $table->string('etablissement_diplome');   // ex: "Université de Yaoundé I"
            $table->integer('annees_experience');
            $table->text('description_experience');    // détail de son parcours
            $table->string('specialite');              // ex: "Orientation scolaire"

            // Si l'admin rejette — il doit obligatoirement donner un motif
            // pour que le conseiller puisse corriger et re-soumettre
            $table->text('motif_rejet')->nullable();

            // Si l'admin demande des infos supplémentaires
            $table->text('commentaire_admin')->nullable();

            // Qui a traité cette demande et quand
            // nullable car pas encore traité à la création
            $table->foreignId('traite_par')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');
            $table->timestamp('traite_le')->nullable();

            // Permet au conseiller de re-soumettre après un rejet
            // On garde l'historique de toutes les tentatives
            $table->integer('tentative')->default(1);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('validations_conseillers');
    }
};