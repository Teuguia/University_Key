<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée la table des règles affichées aux utilisateurs.
     *
     * Elle stocke les textes longs des conditions d'utilisation et de la
     * politique de confidentialité pour pouvoir les gérer depuis la base.
     */
    public function up(): void
    {
        Schema::create('regles', function (Blueprint $table) {
            $table->id();

            // Texte complet des conditions d'utilisation.
            $table->text('conditions');

            // Texte complet de la politique de confidentialité.
            $table->text('politique');

            $table->timestamps();
        });
    }

    /**
     * Supprime la table des règles en cas de rollback.
     */
    public function down(): void
    {
        Schema::dropIfExists('regles');
    }
};
