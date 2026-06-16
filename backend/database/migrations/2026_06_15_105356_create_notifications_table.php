<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cree les notifications internes envoyees aux utilisateurs.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            // Notification applicative simple, distincte des emails/SMS transactionnels.
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('titre');
            $table->text('contenu');
            $table->string('type')->index();
            $table->json('data')->nullable();
            $table->timestamp('lu_le')->nullable()->index();

            $table->timestamps();
        });
    }

    /**
     * Supprime les notifications.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
