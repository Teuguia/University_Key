<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cree les statistiques agregees des conseillers pour le tableau de bord.
     */
    public function up(): void
    {
        Schema::create('stats_conseillers', function (Blueprint $table) {
            $table->id();

            // Une ligne agregee par conseiller pour accelerer l'affichage admin.
            $table->foreignId('conseiller_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('nb_conversations')->default(0);
            $table->unsignedInteger('nb_etudiants_accompagnes')->default(0);
            $table->decimal('note_moyenne', 3, 2)->default(0);
            $table->timestamp('derniere_activite_le')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Supprime les statistiques des conseillers.
     */
    public function down(): void
    {
        Schema::dropIfExists('stats_conseillers');
    }
};
