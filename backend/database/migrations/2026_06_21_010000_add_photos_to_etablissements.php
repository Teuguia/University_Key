<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute une galerie optionnelle au catalogue des etablissements.
     */
    public function up(): void
    {
        if (! Schema::hasTable('etablissements') || Schema::hasColumn('etablissements', 'photos')) {
            return;
        }

        Schema::table('etablissements', function (Blueprint $table): void {
            $table->json('photos')->nullable();
        });
    }

    public function down(): void
    {
        // Conservation non destructive des photos ajoutees au catalogue.
    }
};
