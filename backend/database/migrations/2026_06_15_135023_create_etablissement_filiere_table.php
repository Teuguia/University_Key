<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table pivot — une filière peut exister dans plusieurs établissements
     * et un établissement propose plusieurs filières.
     */
    public function up(): void
    {
        Schema::create('etablissement_filiere', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')
                  ->constrained('etablissements')
                  ->onDelete('cascade');
            $table->foreignId('filiere_id')
                  ->constrained('filieres')
                  ->onDelete('cascade');

            // Frais spécifiques à cet établissement pour cette filière
            $table->integer('frais_specifiques')->nullable();
            $table->timestamps();

            // Empeche de rattacher deux fois la meme filiere au meme etablissement.
            $table->unique(['etablissement_id', 'filiere_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etablissement_filiere');
    }
};
