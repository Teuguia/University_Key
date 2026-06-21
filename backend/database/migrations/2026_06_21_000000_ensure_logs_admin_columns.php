<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Met a niveau les installations plus anciennes qui ont cree logs_admin
     * sans les champs d'audit utilises par l'API.
     */
    public function up(): void
    {
        if (! Schema::hasTable('logs_admin')) {
            return;
        }

        Schema::table('logs_admin', function (Blueprint $table): void {
            if (! Schema::hasColumn('logs_admin', 'admin_id')) {
                $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('logs_admin', 'action')) {
                // Nullable pour ne pas invalider d'eventuelles anciennes lignes.
                $table->string('action')->nullable()->index();
            }

            if (! Schema::hasColumn('logs_admin', 'cible_type')) {
                $table->string('cible_type')->nullable();
            }

            if (! Schema::hasColumn('logs_admin', 'cible_id')) {
                $table->unsignedBigInteger('cible_id')->nullable();
            }

            if (! Schema::hasColumn('logs_admin', 'adresse_ip')) {
                $table->ipAddress('adresse_ip')->nullable();
            }

            if (! Schema::hasColumn('logs_admin', 'user_agent')) {
                $table->text('user_agent')->nullable();
            }

            if (! Schema::hasColumn('logs_admin', 'metadata')) {
                $table->json('metadata')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Les colonnes sont une mise a niveau non destructive : aucun rollback automatique.
    }
};
