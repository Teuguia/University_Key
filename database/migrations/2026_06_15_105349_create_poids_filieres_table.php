<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cree les poids qui relient une reponse a une filiere recommandee.
     */
    public function up(): void
    {
        Schema::create('poids_filieres', function (Blueprint $table) {
            $table->id();

            // Ces poids alimentent le moteur de recommandations apres un test.
            $table->foreignId('choix_reponse_id')->constrained('choix_reponses')->cascadeOnDelete();
            $table->foreignId('filiere_id')->constrained('filieres')->cascadeOnDelete();
            $table->integer('poids')->default(1);
            $table->text('justification')->nullable();

            $table->unique(['choix_reponse_id', 'filiere_id']);
            $table->timestamps();
        });
    }

    /**
     * Supprime les poids de recommandations.
     */
    public function down(): void
    {
        Schema::dropIfExists('poids_filieres');
    }
};
