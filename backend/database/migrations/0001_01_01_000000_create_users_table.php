<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée la table de base des utilisateurs Laravel.
     *
     * Cette migration doit uniquement contenir les champs communs à tous les comptes.
     * Les champs propres à notre plateforme d'orientation sont ajoutés dans une
     * migration séparée pour garder la base Laravel lisible.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Informations minimales nécessaires pour identifier et connecter un utilisateur.
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            // Jeton utilisé par Laravel pour l'option "se souvenir de moi".
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            // Stocke les jetons temporaires de réinitialisation de mot de passe.
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            // Stocke les sessions en base car SESSION_DRIVER=database dans le .env.
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Supprime les tables d'authentification créées par cette migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
