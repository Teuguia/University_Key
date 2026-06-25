<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cree les appareils autorises pour limiter l'acces du compte admin.
     */
    public function up(): void
    {
        Schema::create('admin_devices', function (Blueprint $table) {
            $table->id();

            // On stocke uniquement l'empreinte hachee du navigateur, jamais l'identifiant brut.
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('device_id_hash', 64);
            $table->string('device_name')->nullable();
            $table->text('user_agent')->nullable();
            $table->ipAddress('last_ip')->nullable();
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'device_id_hash']);
        });
    }

    /**
     * Supprime la liste des appareils administrateur.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_devices');
    }
};
