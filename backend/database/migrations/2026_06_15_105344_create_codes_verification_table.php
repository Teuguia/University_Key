<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cette table stocke les codes OTP temporaires
     * pour deux usages distincts :
     * 1. Vérification de l'adresse email après inscription
     * 2. Vérification du numéro de téléphone (MTN / Orange CM)
     *
     * Chaque code expire après 15 minutes pour la sécurité.
     * Une fois utilisé, il est marqué comme 'utilise'
     * et ne peut plus servir — évite la réutilisation.
     */
    public function up(): void
    {
        Schema::create('codes_verification', function (Blueprint $table) {
            $table->id();

            // L'utilisateur à qui appartient ce code
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            // Le code à 6 chiffres envoyé par email ou SMS
            // ex: "847291"
            $table->string('code', 6);

            // Pour savoir si c'est une vérif email ou téléphone
            $table->enum('type', ['email', 'telephone']);

            // La cible : email ou numéro de téléphone
            // ex: "marie@gmail.com" ou "+237691234567"
            $table->string('cible');

            // Statut du code
            $table->enum('statut', [
                'en_attente', // envoyé mais pas encore utilisé
                'utilise',    // vérifié avec succès
                'expire'      // dépassé les 15 minutes
            ])->default('en_attente');

            // Date d'expiration — 15 minutes après création
            // Calculée automatiquement à la création du code
            $table->timestamp('expire_le');

            // Nombre de tentatives — bloque après 5 essais ratés
            // Sécurité anti brute-force sur le code OTP
            $table->integer('nb_tentatives')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('codes_verification');
    }
};