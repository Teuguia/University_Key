<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cree les documents legaux versionnes de la plateforme.
     */
    public function up(): void
    {
        Schema::create('documents_legaux', function (Blueprint $table) {
            $table->id();

            // Les documents sont publies par langue pour couvrir FR/EN.
            $table->enum('type', ['confidentialite', 'conditions', 'mentions_legales'])->index();
            $table->enum('langue', ['fr', 'en'])->default('fr')->index();
            $table->string('titre');
            $table->longText('contenu');
            $table->string('version')->default('1.0');
            $table->boolean('publie')->default(false)->index();
            $table->timestamp('publie_le')->nullable();
            $table->foreignId('cree_par')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Supprime les documents legaux.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents_legaux');
    }
};
