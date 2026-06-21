<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Corrige les bases locales ou le catalogue etablissements existe avec un schema incomplet.
     */
    public function up(): void
    {
        $this->ensureEtablissementsColumns();
        $this->ensureEtablissementFiliereColumns();
    }

    /**
     * Migration corrective non destructive.
     */
    public function down(): void
    {
        //
    }

    private function ensureEtablissementsColumns(): void
    {
        if (! Schema::hasTable('etablissements')) {
            Schema::create('etablissements', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }

        Schema::table('etablissements', function (Blueprint $table) {
            if (! Schema::hasColumn('etablissements', 'nom')) {
                $table->string('nom')->nullable();
            }

            if (! Schema::hasColumn('etablissements', 'type')) {
                $table->enum('type', ['universite_publique', 'universite_privee', 'institut', 'centre_formation', 'ecole_professionnelle'])->default('institut');
            }

            if (! Schema::hasColumn('etablissements', 'ville')) {
                $table->string('ville')->nullable();
            }

            if (! Schema::hasColumn('etablissements', 'region')) {
                $table->enum('region', ['Adamaoua', 'Centre', 'Est', 'Extrême-Nord', 'Littoral', 'Nord', 'Nord-Ouest', 'Ouest', 'Sud', 'Sud-Ouest'])->nullable();
            }

            if (! Schema::hasColumn('etablissements', 'adresse')) {
                $table->string('adresse')->nullable();
            }

            if (! Schema::hasColumn('etablissements', 'telephone')) {
                $table->string('telephone', 20)->nullable();
            }

            if (! Schema::hasColumn('etablissements', 'email')) {
                $table->string('email')->nullable();
            }

            if (! Schema::hasColumn('etablissements', 'site_web')) {
                $table->string('site_web')->nullable();
            }

            if (! Schema::hasColumn('etablissements', 'logo')) {
                $table->string('logo')->nullable();
            }

            if (! Schema::hasColumn('etablissements', 'description')) {
                $table->text('description')->nullable();
            }

            if (! Schema::hasColumn('etablissements', 'frais_min')) {
                $table->integer('frais_min')->nullable();
            }

            if (! Schema::hasColumn('etablissements', 'frais_max')) {
                $table->integer('frais_max')->nullable();
            }

            if (! Schema::hasColumn('etablissements', 'conditions_admission')) {
                $table->text('conditions_admission')->nullable();
            }

            if (! Schema::hasColumn('etablissements', 'a_concours')) {
                $table->boolean('a_concours')->default(false);
            }

            if (! Schema::hasColumn('etablissements', 'details_concours')) {
                $table->text('details_concours')->nullable();
            }

            if (! Schema::hasColumn('etablissements', 'statut')) {
                $table->enum('statut', ['en_attente', 'valide', 'rejete'])->default('en_attente');
            }

            if (! Schema::hasColumn('etablissements', 'valide')) {
                $table->boolean('valide')->default(false);
            }
        });
    }

    private function ensureEtablissementFiliereColumns(): void
    {
        if (! Schema::hasTable('etablissement_filiere')) {
            Schema::create('etablissement_filiere', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }

        Schema::table('etablissement_filiere', function (Blueprint $table) {
            if (! Schema::hasColumn('etablissement_filiere', 'etablissement_id')) {
                $table->foreignId('etablissement_id')->nullable()->constrained('etablissements')->cascadeOnDelete();
            }

            if (! Schema::hasColumn('etablissement_filiere', 'filiere_id')) {
                $table->foreignId('filiere_id')->nullable()->constrained('filieres')->cascadeOnDelete();
            }

            if (! Schema::hasColumn('etablissement_filiere', 'frais_specifiques')) {
                $table->integer('frais_specifiques')->nullable();
            }
        });
    }
};
