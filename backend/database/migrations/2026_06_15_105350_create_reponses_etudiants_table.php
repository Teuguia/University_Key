<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cree les reponses donnees par un etudiant pendant une session de test.
     */
    public function up(): void
    {
        Schema::create('reponses_etudiants', function (Blueprint $table) {
            $table->id();

            // La contrainte SQL vers sessions_test est evitee ici car cette migration peut etre
            // executee avant la creation de sessions_test a cause du tri par nom.
            $table->foreignId('session_test_id')->index();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->foreignId('choix_reponse_id')->nullable()->constrained('choix_reponses')->nullOnDelete();
            $table->text('reponse_libre')->nullable();
            $table->integer('valeur')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Supprime les reponses des etudiants.
     */
    public function down(): void
    {
        Schema::dropIfExists('reponses_etudiants');
    }
};
