<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Corrige les bases locales ou profils_etudiants existe sans toutes ses colonnes.
     */
    public function up(): void
    {
        if (! Schema::hasTable('profils_etudiants')) {
            Schema::create('profils_etudiants', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }

        Schema::table('profils_etudiants', function (Blueprint $table) {
            if (! Schema::hasColumn('profils_etudiants', 'user_id')) {
                $table->foreignId('user_id')->nullable()->unique()->constrained('users')->onDelete('cascade');
            }

            if (! Schema::hasColumn('profils_etudiants', 'prenom')) {
                $table->string('prenom')->nullable();
            }

            if (! Schema::hasColumn('profils_etudiants', 'nom')) {
                $table->string('nom')->nullable();
            }

            if (! Schema::hasColumn('profils_etudiants', 'date_naissance')) {
                $table->date('date_naissance')->nullable();
            }

            if (! Schema::hasColumn('profils_etudiants', 'sexe')) {
                $table->enum('sexe', ['M', 'F', 'non_precise'])->nullable();
            }

            if (! Schema::hasColumn('profils_etudiants', 'ville')) {
                $table->string('ville')->nullable();
            }

            if (! Schema::hasColumn('profils_etudiants', 'region')) {
                $table->enum('region', [
                    'Adamaoua',
                    'Centre',
                    'Est',
                    'Extrême-Nord',
                    'Littoral',
                    'Nord',
                    'Nord-Ouest',
                    'Ouest',
                    'Sud',
                    'Sud-Ouest',
                ])->nullable();
            }

            if (! Schema::hasColumn('profils_etudiants', 'type_bac')) {
                $table->enum('type_bac', ['A', 'C', 'D', 'E', 'F1', 'F2', 'F3', 'F4', 'TI', 'GCE_AL', 'GCE_OL', 'autre'])->nullable();
            }

            if (! Schema::hasColumn('profils_etudiants', 'annee_bac')) {
                $table->year('annee_bac')->nullable();
            }

            if (! Schema::hasColumn('profils_etudiants', 'moyenne_generale')) {
                $table->decimal('moyenne_generale', 4, 2)->nullable();
            }

            if (! Schema::hasColumn('profils_etudiants', 'matieres_fortes')) {
                $table->json('matieres_fortes')->nullable();
            }

            if (! Schema::hasColumn('profils_etudiants', 'matieres_faibles')) {
                $table->json('matieres_faibles')->nullable();
            }

            if (! Schema::hasColumn('profils_etudiants', 'budget_annuel')) {
                $table->enum('budget_annuel', ['55.000 FCFA', '500.000', '2.000.000'])->nullable();
            }

            if (! Schema::hasColumn('profils_etudiants', 'centres_interet')) {
                $table->json('centres_interet')->nullable();
            }

            if (! Schema::hasColumn('profils_etudiants', 'objectif_professionnel')) {
                $table->text('objectif_professionnel')->nullable();
            }

            if (! Schema::hasColumn('profils_etudiants', 'preference_public')) {
                $table->boolean('preference_public')->nullable();
            }

            if (! Schema::hasColumn('profils_etudiants', 'mobilite')) {
                $table->boolean('mobilite')->default(false);
            }

            if (! Schema::hasColumn('profils_etudiants', 'photo')) {
                $table->string('photo')->nullable();
            }
        });
    }

    /**
     * Migration corrective non destructive.
     */
    public function down(): void
    {
        //
    }
};
