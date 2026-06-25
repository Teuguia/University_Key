<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Corrige les bases locales ou profils_conseillers existe sans toutes ses colonnes.
     */
    public function up(): void
    {
        if (! Schema::hasTable('profils_conseillers')) {
            Schema::create('profils_conseillers', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }

        Schema::table('profils_conseillers', function (Blueprint $table) {
            if (! Schema::hasColumn('profils_conseillers', 'user_id')) {
                $table->foreignId('user_id')->nullable()->unique()->constrained('users')->onDelete('cascade');
            }

            if (! Schema::hasColumn('profils_conseillers', 'prenom')) {
                $table->string('prenom')->nullable();
            }

            if (! Schema::hasColumn('profils_conseillers', 'nom')) {
                $table->string('nom')->nullable();
            }

            if (! Schema::hasColumn('profils_conseillers', 'ville')) {
                $table->string('ville')->nullable();
            }

            if (! Schema::hasColumn('profils_conseillers', 'region')) {
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

            if (! Schema::hasColumn('profils_conseillers', 'specialite')) {
                $table->string('specialite')->nullable();
            }

            if (! Schema::hasColumn('profils_conseillers', 'annees_experience')) {
                $table->integer('annees_experience')->default(0);
            }

            if (! Schema::hasColumn('profils_conseillers', 'biographie')) {
                $table->text('biographie')->nullable();
            }

            if (! Schema::hasColumn('profils_conseillers', 'diplomes')) {
                $table->json('diplomes')->nullable();
            }

            if (! Schema::hasColumn('profils_conseillers', 'cv_path')) {
                $table->string('cv_path')->nullable();
            }

            if (! Schema::hasColumn('profils_conseillers', 'documents_path')) {
                $table->json('documents_path')->nullable();
            }

            if (! Schema::hasColumn('profils_conseillers', 'disponible')) {
                $table->boolean('disponible')->default(true);
            }

            if (! Schema::hasColumn('profils_conseillers', 'disponibilite_details')) {
                $table->text('disponibilite_details')->nullable();
            }

            if (! Schema::hasColumn('profils_conseillers', 'note_moyenne')) {
                $table->decimal('note_moyenne', 3, 2)->default(0.00);
            }

            if (! Schema::hasColumn('profils_conseillers', 'nb_etudiants_accompagnes')) {
                $table->integer('nb_etudiants_accompagnes')->default(0);
            }

            if (! Schema::hasColumn('profils_conseillers', 'photo')) {
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
