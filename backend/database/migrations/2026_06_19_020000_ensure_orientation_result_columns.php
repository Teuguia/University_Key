<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Corrige les bases locales ou les tables de resultats de tests sont incompletes.
     */
    public function up(): void
    {
        $this->ensureSessionsTestColumns();
        $this->ensureReponsesEtudiantsColumns();
        $this->ensureRecommandationsColumns();
    }

    /**
     * Migration corrective non destructive.
     */
    public function down(): void
    {
        //
    }

    private function ensureSessionsTestColumns(): void
    {
        if (! Schema::hasTable('sessions_test')) {
            Schema::create('sessions_test', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }

        Schema::table('sessions_test', function (Blueprint $table) {
            if (! Schema::hasColumn('sessions_test', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            }

            if (! Schema::hasColumn('sessions_test', 'test_orientation_id')) {
                $table->foreignId('test_orientation_id')->nullable()->constrained('tests_orientation')->cascadeOnDelete();
            }

            if (! Schema::hasColumn('sessions_test', 'statut')) {
                $table->enum('statut', ['en_cours', 'termine', 'abandonne'])->default('en_cours')->index();
            }

            if (! Schema::hasColumn('sessions_test', 'score_global')) {
                $table->decimal('score_global', 5, 2)->nullable();
            }

            if (! Schema::hasColumn('sessions_test', 'commence_le')) {
                $table->timestamp('commence_le')->nullable();
            }

            if (! Schema::hasColumn('sessions_test', 'termine_le')) {
                $table->timestamp('termine_le')->nullable();
            }

            if (! Schema::hasColumn('sessions_test', 'metadata')) {
                $table->json('metadata')->nullable();
            }
        });
    }

    private function ensureReponsesEtudiantsColumns(): void
    {
        if (! Schema::hasTable('reponses_etudiants')) {
            Schema::create('reponses_etudiants', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }

        Schema::table('reponses_etudiants', function (Blueprint $table) {
            if (! Schema::hasColumn('reponses_etudiants', 'session_test_id')) {
                $table->foreignId('session_test_id')->nullable()->index();
            }

            if (! Schema::hasColumn('reponses_etudiants', 'question_id')) {
                $table->foreignId('question_id')->nullable()->constrained('questions')->cascadeOnDelete();
            }

            if (! Schema::hasColumn('reponses_etudiants', 'choix_reponse_id')) {
                $table->foreignId('choix_reponse_id')->nullable()->constrained('choix_reponses')->nullOnDelete();
            }

            if (! Schema::hasColumn('reponses_etudiants', 'reponse_libre')) {
                $table->text('reponse_libre')->nullable();
            }

            if (! Schema::hasColumn('reponses_etudiants', 'valeur')) {
                $table->integer('valeur')->nullable();
            }
        });
    }

    private function ensureRecommandationsColumns(): void
    {
        if (! Schema::hasTable('recommandations')) {
            Schema::create('recommandations', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }

        Schema::table('recommandations', function (Blueprint $table) {
            if (! Schema::hasColumn('recommandations', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            }

            if (! Schema::hasColumn('recommandations', 'session_test_id')) {
                $table->foreignId('session_test_id')->nullable()->constrained('sessions_test')->nullOnDelete();
            }

            if (! Schema::hasColumn('recommandations', 'filiere_id')) {
                $table->foreignId('filiere_id')->nullable()->constrained('filieres')->cascadeOnDelete();
            }

            if (! Schema::hasColumn('recommandations', 'etablissement_id')) {
                $table->foreignId('etablissement_id')->nullable()->constrained('etablissements')->nullOnDelete();
            }

            if (! Schema::hasColumn('recommandations', 'score')) {
                $table->decimal('score', 5, 2)->default(0);
            }

            if (! Schema::hasColumn('recommandations', 'explication')) {
                $table->text('explication')->nullable();
            }

            if (! Schema::hasColumn('recommandations', 'rang')) {
                $table->unsignedInteger('rang')->default(1);
            }
        });
    }
};
