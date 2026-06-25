<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cree les favoris des utilisateurs pour les filieres, ecoles ou metiers.
     */
    public function up(): void
    {
        Schema::create('favoris', function (Blueprint $table) {
            $table->id();

            // Relation polymorphe pour eviter une table favorite par type de contenu.
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->morphs('favoritable');
            $table->string('note')->nullable();

            $table->unique(['user_id', 'favoritable_id', 'favoritable_type']);
            $table->timestamps();
        });
    }

    /**
     * Supprime les favoris.
     */
    public function down(): void
    {
        Schema::dropIfExists('favoris');
    }
};
