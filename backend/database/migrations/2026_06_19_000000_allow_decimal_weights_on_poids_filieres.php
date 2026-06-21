<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Autorise les poids decimaux, par exemple 0.5 dans le test general.
     */
    public function up(): void
    {
        if (! Schema::hasTable('poids_filieres') || ! Schema::hasColumn('poids_filieres', 'poids')) {
            return;
        }

        match (DB::getDriverName()) {
            'pgsql' => DB::statement('ALTER TABLE poids_filieres ALTER COLUMN poids TYPE NUMERIC(5,2) USING poids::numeric'),
            'mysql' => DB::statement('ALTER TABLE poids_filieres MODIFY poids DECIMAL(5,2) NOT NULL DEFAULT 1'),
            default => null,
        };
    }

    /**
     * Revient a un entier arrondi si necessaire.
     */
    public function down(): void
    {
        if (! Schema::hasTable('poids_filieres') || ! Schema::hasColumn('poids_filieres', 'poids')) {
            return;
        }

        match (DB::getDriverName()) {
            'pgsql' => DB::statement('ALTER TABLE poids_filieres ALTER COLUMN poids TYPE INTEGER USING ROUND(poids)::integer'),
            'mysql' => DB::statement('ALTER TABLE poids_filieres MODIFY poids INTEGER NOT NULL DEFAULT 1'),
            default => null,
        };
    }
};
