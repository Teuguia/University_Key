<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cree les opportunites: concours, bourses, admissions et evenements utiles.
     */
    public function up(): void
    {
        Schema::create('opportunites', function (Blueprint $table) {
            $table->id();

            // Une opportunite peut etre generale ou rattachee a une ecole/filiere precise.
            $table->foreignId('etablissement_id')->nullable()->constrained('etablissements')->nullOnDelete();
            $table->foreignId('filiere_id')->nullable()->constrained('filieres')->nullOnDelete();

            // Contenu public consultable par les visiteurs et les etudiants.
            $table->string('titre');
            $table->enum('type', ['concours', 'bourse', 'admission', 'evenement'])->index();
            $table->text('description')->nullable();
            $table->text('conditions')->nullable();
            $table->integer('frais')->nullable();
            $table->date('date_ouverture')->nullable();
            $table->date('date_cloture')->nullable()->index();
            $table->string('lien_officiel')->nullable();
            $table->enum('statut', ['brouillon', 'publie', 'archive'])->default('brouillon')->index();
            $table->enum('langue', ['fr', 'en'])->default('fr');

            $table->timestamps();
        });
    }

    /**
     * Supprime les opportunites et concours.
     */
    public function down(): void
    {
        Schema::dropIfExists('opportunites');
    }
};
