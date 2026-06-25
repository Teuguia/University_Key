<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cree les tests d'orientation proposes aux etudiants.
     */
    public function up(): void
    {
        Schema::create('tests_orientation', function (Blueprint $table) {
            $table->id();

            // Metadonnees du test pour versionner les questionnaires FR/EN.
            $table->string('titre');
            $table->text('description')->nullable();
            $table->enum('langue', ['fr', 'en'])->default('fr')->index();
            $table->unsignedInteger('version')->default(1);
            $table->unsignedSmallInteger('duree_minutes')->default(20);
            $table->enum('statut', ['brouillon', 'publie', 'archive'])->default('brouillon')->index();
            $table->foreignId('cree_par')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Supprime les tests d'orientation.
     */
    public function down(): void
    {
        Schema::dropIfExists('tests_orientation');
    }
};
