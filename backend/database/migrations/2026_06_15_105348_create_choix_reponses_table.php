<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cree les choix possibles pour les questions d'orientation.
     */
    public function up(): void
    {
        Schema::create('choix_reponses', function (Blueprint $table) {
            $table->id();

            // La contrainte SQL vers questions est evitee ici car cette migration a le meme timestamp
            // et peut etre triee avant celle des questions selon le nom du fichier.
            $table->foreignId('question_id')->index();
            $table->string('libelle');
            $table->unsignedInteger('ordre')->default(1);
            $table->integer('valeur')->default(0);
            $table->json('metadata')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Supprime les choix de reponses.
     */
    public function down(): void
    {
        Schema::dropIfExists('choix_reponses');
    }
};
