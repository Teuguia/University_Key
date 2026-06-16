<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cree une session de test pour chaque passage d'un etudiant.
     */
    public function up(): void
    {
        Schema::create('sessions_test', function (Blueprint $table) {
            $table->id();

            // Une session permet de reprendre ou auditer un test commence.
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('test_orientation_id')->constrained('tests_orientation')->cascadeOnDelete();
            $table->enum('statut', ['en_cours', 'termine', 'abandonne'])->default('en_cours')->index();
            $table->decimal('score_global', 5, 2)->nullable();
            $table->timestamp('commence_le')->nullable();
            $table->timestamp('termine_le')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Supprime les sessions de test.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions_test');
    }
};
