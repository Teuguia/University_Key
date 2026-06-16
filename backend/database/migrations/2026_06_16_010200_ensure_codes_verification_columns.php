<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Corrige les bases locales ou codes_verification existe sans toutes ses colonnes OTP.
     */
    public function up(): void
    {
        if (! Schema::hasTable('codes_verification')) {
            Schema::create('codes_verification', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }

        Schema::table('codes_verification', function (Blueprint $table) {
            if (! Schema::hasColumn('codes_verification', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            }

            if (! Schema::hasColumn('codes_verification', 'code')) {
                $table->string('code', 6)->nullable();
            }

            if (! Schema::hasColumn('codes_verification', 'type')) {
                $table->enum('type', ['email', 'telephone'])->nullable();
            }

            if (! Schema::hasColumn('codes_verification', 'cible')) {
                $table->string('cible')->nullable();
            }

            if (! Schema::hasColumn('codes_verification', 'statut')) {
                $table->enum('statut', ['en_attente', 'utilise', 'expire'])->default('en_attente');
            }

            if (! Schema::hasColumn('codes_verification', 'expire_le')) {
                $table->timestamp('expire_le')->nullable();
            }

            if (! Schema::hasColumn('codes_verification', 'nb_tentatives')) {
                $table->integer('nb_tentatives')->default(0);
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
