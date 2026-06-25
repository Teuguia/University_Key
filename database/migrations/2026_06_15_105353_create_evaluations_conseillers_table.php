<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cree les evaluations donnees aux conseillers apres un accompagnement.
     */
    public function up(): void
    {
        Schema::create('evaluations_conseillers', function (Blueprint $table) {
            $table->id();

            // L'evaluation relie un etudiant, un conseiller et eventuellement une conversation.
            $table->foreignId('conseiller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('etudiant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained('conversations')->nullOnDelete();
            $table->unsignedTinyInteger('note');
            $table->text('commentaire')->nullable();
            $table->enum('statut', ['publie', 'masque', 'signale'])->default('publie')->index();

            $table->timestamps();
        });
    }

    /**
     * Supprime les evaluations des conseillers.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluations_conseillers');
    }
};
