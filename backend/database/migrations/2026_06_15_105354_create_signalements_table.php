<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cree les signalements moderes par les administrateurs.
     */
    public function up(): void
    {
        Schema::create('signalements', function (Blueprint $table) {
            $table->id();

            // Le signalement peut viser un utilisateur, un message ou un contenu externe.
            $table->foreignId('signale_par')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_signale_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('message_id')->nullable()->constrained('messages')->nullOnDelete();
            $table->string('cible_type')->nullable();
            $table->unsignedBigInteger('cible_id')->nullable();
            $table->string('motif');
            $table->text('description')->nullable();
            $table->enum('statut', ['nouveau', 'en_cours', 'resolu', 'rejete'])->default('nouveau')->index();
            $table->foreignId('traite_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('traite_le')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Supprime les signalements.
     */
    public function down(): void
    {
        Schema::dropIfExists('signalements');
    }
};
