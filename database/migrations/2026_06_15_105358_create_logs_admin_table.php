<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cree les journaux d'activite administrateur pour l'audit et la securite.
     */
    public function up(): void
    {
        Schema::create('logs_admin', function (Blueprint $table) {
            $table->id();

            // Chaque action sensible garde son auteur, sa cible et son contexte technique.
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action')->index();
            $table->string('cible_type')->nullable();
            $table->unsignedBigInteger('cible_id')->nullable();
            $table->ipAddress('adresse_ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Supprime les journaux administrateur.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs_admin');
    }
};
