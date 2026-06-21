<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Corrige les bases locales ou favoris existe sans le schema polymorphe complet.
     */
    public function up(): void
    {
        if (! Schema::hasTable('favoris')) {
            // Certaines bases locales ont saute la migration initiale: on cree une base minimale.
            Schema::create('favoris', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }

        Schema::table('favoris', function (Blueprint $table) {
            // Les colonnes restent nullable pour corriger sans casser des donnees deja presentes.
            if (! Schema::hasColumn('favoris', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            }

            if (! Schema::hasColumn('favoris', 'favoritable_id')) {
                $table->unsignedBigInteger('favoritable_id')->nullable();
            }

            if (! Schema::hasColumn('favoris', 'favoritable_type')) {
                $table->string('favoritable_type')->nullable();
            }

            if (! Schema::hasColumn('favoris', 'note')) {
                $table->string('note')->nullable();
            }

            if (! Schema::hasColumn('favoris', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }

            if (! Schema::hasColumn('favoris', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
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
