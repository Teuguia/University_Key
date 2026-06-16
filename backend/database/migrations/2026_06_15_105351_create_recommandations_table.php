<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cree les recommandations calculees apres un test d'orientation.
     */
    public function up(): void
    {
        Schema::create('recommandations', function (Blueprint $table) {
            $table->id();

            // Une recommandation relie l'etudiant a une filiere et parfois une ecole precise.
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('session_test_id')->nullable()->constrained('sessions_test')->nullOnDelete();
            $table->foreignId('filiere_id')->constrained('filieres')->cascadeOnDelete();
            $table->foreignId('etablissement_id')->nullable()->constrained('etablissements')->nullOnDelete();
            $table->decimal('score', 5, 2)->default(0);
            $table->text('explication')->nullable();
            $table->unsignedInteger('rang')->default(1);

            $table->timestamps();
        });
    }

    /**
     * Supprime les recommandations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recommandations');
    }
};
