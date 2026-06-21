<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Corrige les bases locales ou les migrations des tests sont marquees comme executees
     * alors que les tables ne contiennent que id/created_at/updated_at.
     */
    public function up(): void
    {
        $this->ensureFilieresColumns();
        $this->ensureTestsOrientationColumns();
        $this->ensureQuestionsColumns();
        $this->ensureChoixReponsesColumns();
        $this->ensurePoidsFilieresColumns();
    }

    /**
     * Migration corrective non destructive.
     */
    public function down(): void
    {
        //
    }

    private function ensureFilieresColumns(): void
    {
        if (! Schema::hasTable('filieres')) {
            Schema::create('filieres', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }

        Schema::table('filieres', function (Blueprint $table) {
            if (! Schema::hasColumn('filieres', 'nom')) {
                $table->string('nom')->nullable();
            }

            if (! Schema::hasColumn('filieres', 'domaine')) {
                $table->string('domaine')->nullable()->index();
            }

            if (! Schema::hasColumn('filieres', 'description')) {
                $table->text('description')->nullable();
            }

            if (! Schema::hasColumn('filieres', 'niveau')) {
                $table->enum('niveau', ['BTS', 'HND', 'Licence', 'Master', 'Doctorat', 'Formation_professionnelle', 'Classe_preparatoire'])->nullable();
            }

            if (! Schema::hasColumn('filieres', 'duree_annees')) {
                $table->integer('duree_annees')->nullable();
            }

            if (! Schema::hasColumn('filieres', 'diplome_obtenu')) {
                $table->string('diplome_obtenu')->nullable();
            }

            if (! Schema::hasColumn('filieres', 'conditions_acces')) {
                $table->text('conditions_acces')->nullable();
            }

            if (! Schema::hasColumn('filieres', 'debouches')) {
                $table->text('debouches')->nullable();
            }

            if (! Schema::hasColumn('filieres', 'metiers_associes')) {
                $table->json('metiers_associes')->nullable();
            }

            if (! Schema::hasColumn('filieres', 'competences_requises')) {
                $table->json('competences_requises')->nullable();
            }

            if (! Schema::hasColumn('filieres', 'active')) {
                $table->boolean('active')->default(true)->index();
            }
        });
    }

    private function ensureTestsOrientationColumns(): void
    {
        if (! Schema::hasTable('tests_orientation')) {
            Schema::create('tests_orientation', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }

        Schema::table('tests_orientation', function (Blueprint $table) {
            if (! Schema::hasColumn('tests_orientation', 'titre')) {
                $table->string('titre')->nullable();
            }

            if (! Schema::hasColumn('tests_orientation', 'description')) {
                $table->text('description')->nullable();
            }

            if (! Schema::hasColumn('tests_orientation', 'langue')) {
                $table->enum('langue', ['fr', 'en'])->default('fr')->index();
            }

            if (! Schema::hasColumn('tests_orientation', 'version')) {
                $table->unsignedInteger('version')->default(1);
            }

            if (! Schema::hasColumn('tests_orientation', 'duree_minutes')) {
                $table->unsignedSmallInteger('duree_minutes')->default(20);
            }

            if (! Schema::hasColumn('tests_orientation', 'statut')) {
                $table->enum('statut', ['brouillon', 'publie', 'archive'])->default('brouillon')->index();
            }

            if (! Schema::hasColumn('tests_orientation', 'cree_par')) {
                $table->foreignId('cree_par')->nullable()->constrained('users')->nullOnDelete();
            }
        });
    }

    private function ensureQuestionsColumns(): void
    {
        if (! Schema::hasTable('questions')) {
            Schema::create('questions', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }

        Schema::table('questions', function (Blueprint $table) {
            if (! Schema::hasColumn('questions', 'test_orientation_id')) {
                $table->foreignId('test_orientation_id')->nullable()->constrained('tests_orientation')->cascadeOnDelete();
            }

            if (! Schema::hasColumn('questions', 'libelle')) {
                $table->text('libelle')->nullable();
            }

            if (! Schema::hasColumn('questions', 'type')) {
                $table->enum('type', ['choix_unique', 'choix_multiple', 'echelle'])->default('choix_unique');
            }

            if (! Schema::hasColumn('questions', 'domaine')) {
                $table->string('domaine')->nullable()->index();
            }

            if (! Schema::hasColumn('questions', 'ordre')) {
                $table->unsignedInteger('ordre')->default(1);
            }

            if (! Schema::hasColumn('questions', 'obligatoire')) {
                $table->boolean('obligatoire')->default(true);
            }

            if (! Schema::hasColumn('questions', 'active')) {
                $table->boolean('active')->default(true)->index();
            }
        });
    }

    private function ensureChoixReponsesColumns(): void
    {
        if (! Schema::hasTable('choix_reponses')) {
            Schema::create('choix_reponses', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }

        Schema::table('choix_reponses', function (Blueprint $table) {
            if (! Schema::hasColumn('choix_reponses', 'question_id')) {
                $table->foreignId('question_id')->nullable()->index();
            }

            if (! Schema::hasColumn('choix_reponses', 'libelle')) {
                $table->string('libelle')->nullable();
            }

            if (! Schema::hasColumn('choix_reponses', 'ordre')) {
                $table->unsignedInteger('ordre')->default(1);
            }

            if (! Schema::hasColumn('choix_reponses', 'valeur')) {
                $table->integer('valeur')->default(0);
            }

            if (! Schema::hasColumn('choix_reponses', 'metadata')) {
                $table->json('metadata')->nullable();
            }
        });
    }

    private function ensurePoidsFilieresColumns(): void
    {
        if (! Schema::hasTable('poids_filieres')) {
            Schema::create('poids_filieres', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }

        Schema::table('poids_filieres', function (Blueprint $table) {
            if (! Schema::hasColumn('poids_filieres', 'choix_reponse_id')) {
                $table->foreignId('choix_reponse_id')->nullable()->constrained('choix_reponses')->cascadeOnDelete();
            }

            if (! Schema::hasColumn('poids_filieres', 'filiere_id')) {
                $table->foreignId('filiere_id')->nullable()->constrained('filieres')->cascadeOnDelete();
            }

            if (! Schema::hasColumn('poids_filieres', 'poids')) {
                $table->decimal('poids', 5, 2)->default(1);
            }

            if (! Schema::hasColumn('poids_filieres', 'justification')) {
                $table->text('justification')->nullable();
            }
        });
    }
};
