<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Corrige les bases locales ou la migration role/statut est marquee comme executee
     * alors que certaines colonnes attendues par l'authentification sont absentes.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['admin', 'conseiller', 'etudiant'])->default('etudiant');
            }

            if (! Schema::hasColumn('users', 'statut')) {
                $table->enum('statut', ['actif', 'en_attente', 'rejete', 'suspendu'])->default('actif');
            }

            if (! Schema::hasColumn('users', 'telephone')) {
                $table->string('telephone', 20)->nullable();
            }

            if (! Schema::hasColumn('users', 'langue_preferee')) {
                $table->enum('langue_preferee', ['fr', 'en'])->default('fr');
            }

            if (! Schema::hasColumn('users', 'derniere_connexion')) {
                $table->timestamp('derniere_connexion')->nullable();
            }
        });
    }

    /**
     * Cette migration est corrective: le rollback volontaire ne supprime pas ces colonnes
     * car elles appartiennent au schema fonctionnel de users.
     */
    public function down(): void
    {
        //
    }
};
