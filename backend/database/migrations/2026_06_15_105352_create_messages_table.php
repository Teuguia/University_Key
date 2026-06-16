<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cree les messages echanges dans les conversations.
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();

            // Chaque message appartient a une conversation et a un expediteur.
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('expediteur_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['texte', 'fichier', 'systeme'])->default('texte');
            $table->text('contenu');
            $table->string('piece_jointe_path')->nullable();
            $table->timestamp('lu_le')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Supprime les messages.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
