<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute les informations propres à notre plateforme sur les comptes utilisateurs.
     *
     * Ces champs permettent de distinguer les étudiants, conseillers et admins,
     * puis de gérer l'état du compte sans mélanger cette logique avec la
     * migration Laravel de base.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Rôle fonctionnel utilisé pour adapter les accès et les interfaces.
            $table->enum('role', ['admin', 'conseiller', 'etudiant'])
                  ->default('etudiant');

            // Statut administratif du compte sur la plateforme.
            $table->enum('statut', ['actif', 'en_attente', 'rejete', 'suspendu'])
                  ->default('actif');

            // Coordonnées et préférences utiles pour les notifications et l'expérience utilisateur.
            $table->string('telephone', 20)->nullable();
            $table->enum('langue_preferee', ['fr', 'en'])
                  ->default('fr');

            // Dernière connexion connue, utile pour le suivi et la sécurité.
            $table->timestamp('derniere_connexion')->nullable();
        });
    }

    /**
     * Retire les champs spécifiques à la plateforme en cas de rollback.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role',
                'statut',
                'telephone',
                'langue_preferee',
                'derniere_connexion',
            ]);
        });
    }
};
