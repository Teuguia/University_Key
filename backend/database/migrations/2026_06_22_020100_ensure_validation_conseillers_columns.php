<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Certaines bases locales portent la migration initiale comme executee
     * alors que la table est incomplete. Cette correction est additive et ne
     * modifie aucune demande de validation existante.
     */
    public function up(): void
    {
        if (! Schema::hasTable('validations_conseillers')) {
            Schema::create('validations_conseillers', function (Blueprint $table): void {
                $table->id();
                $table->timestamps();
            });
        }

        Schema::table('validations_conseillers', function (Blueprint $table): void {
            if (! Schema::hasColumn('validations_conseillers', 'conseiller_id')) {
                $table->foreignId('conseiller_id')->nullable()->constrained('users')->cascadeOnDelete();
            }

            if (! Schema::hasColumn('validations_conseillers', 'statut')) {
                $table->string('statut')->default('en_attente');
            }

            if (! Schema::hasColumn('validations_conseillers', 'diplome_principal')) {
                $table->string('diplome_principal')->nullable();
            }

            if (! Schema::hasColumn('validations_conseillers', 'etablissement_diplome')) {
                $table->string('etablissement_diplome')->nullable();
            }

            if (! Schema::hasColumn('validations_conseillers', 'annees_experience')) {
                $table->integer('annees_experience')->default(0);
            }

            if (! Schema::hasColumn('validations_conseillers', 'description_experience')) {
                $table->text('description_experience')->nullable();
            }

            if (! Schema::hasColumn('validations_conseillers', 'specialite')) {
                $table->string('specialite')->nullable();
            }

            if (! Schema::hasColumn('validations_conseillers', 'motif_rejet')) {
                $table->text('motif_rejet')->nullable();
            }

            if (! Schema::hasColumn('validations_conseillers', 'commentaire_admin')) {
                $table->text('commentaire_admin')->nullable();
            }

            if (! Schema::hasColumn('validations_conseillers', 'traite_par')) {
                $table->foreignId('traite_par')->nullable()->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('validations_conseillers', 'traite_le')) {
                $table->timestamp('traite_le')->nullable();
            }

            if (! Schema::hasColumn('validations_conseillers', 'tentative')) {
                $table->integer('tentative')->default(1);
            }
        });
    }

    public function down(): void
    {
        // Correctif volontairement non destructif pour les bases existantes.
    }
};
