<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cree les conversations entre etudiants et conseillers.
     */
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();

            // Les participants sont des utilisateurs avec roles differents.
            $table->foreignId('etudiant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('conseiller_id')->constrained('users')->cascadeOnDelete();
            $table->string('sujet')->nullable();
            $table->enum('statut', ['ouverte', 'fermee', 'signalee'])->default('ouverte')->index();
            $table->timestamp('dernier_message_le')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Supprime les conversations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
