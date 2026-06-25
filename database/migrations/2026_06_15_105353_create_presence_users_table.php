<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cree le suivi de presence pour afficher les utilisateurs disponibles.
     */
    public function up(): void
    {
        Schema::create('presence_users', function (Blueprint $table) {
            $table->id();

            // Un utilisateur ne doit avoir qu'un seul etat de presence courant.
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->enum('statut', ['en_ligne', 'absent', 'hors_ligne'])->default('hors_ligne')->index();
            $table->timestamp('last_seen_at')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Supprime les informations de presence.
     */
    public function down(): void
    {
        Schema::dropIfExists('presence_users');
    }
};
