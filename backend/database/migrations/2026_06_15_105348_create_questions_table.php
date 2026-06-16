<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cree les questions rattachees a un test d'orientation.
     */
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();

            // Chaque question appartient a un test et garde un ordre d'affichage stable.
            $table->foreignId('test_orientation_id')->constrained('tests_orientation')->cascadeOnDelete();
            $table->text('libelle');
            $table->enum('type', ['choix_unique', 'choix_multiple', 'echelle'])->default('choix_unique');
            $table->string('domaine')->nullable()->index();
            $table->unsignedInteger('ordre')->default(1);
            $table->boolean('obligatoire')->default(true);
            $table->boolean('active')->default(true)->index();

            $table->timestamps();
        });
    }

    /**
     * Supprime les questions des tests.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
