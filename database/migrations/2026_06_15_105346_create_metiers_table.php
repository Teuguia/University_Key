<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cree le catalogue des metiers presentes aux etudiants apres les recommandations.
     */
    public function up(): void
    {
        Schema::create('metiers', function (Blueprint $table) {
            $table->id();

            // Informations de base affichees dans le catalogue des debouches.
            $table->string('nom')->unique();
            $table->string('domaine')->index();
            $table->text('description')->nullable();
            $table->json('competences_requises')->nullable();
            $table->integer('salaire_min')->nullable();
            $table->integer('salaire_max')->nullable();
            $table->boolean('active')->default(true)->index();

            $table->timestamps();
        });
    }

    /**
     * Supprime le catalogue des metiers.
     */
    public function down(): void
    {
        Schema::dropIfExists('metiers');
    }
};
