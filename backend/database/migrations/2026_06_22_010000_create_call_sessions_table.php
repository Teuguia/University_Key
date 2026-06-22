<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Signaux WebRTC d'un appel audio entre les deux participants d'une conversation.
     * Les flux audio ne transitent pas par la base : seules les offres/reponses SDP
     * necessaires a la negociation sont conservees temporairement.
     */
    public function up(): void
    {
        Schema::create('call_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('initiator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['ringing', 'accepted', 'rejected', 'cancelled', 'ended', 'missed'])->default('ringing')->index();
            $table->json('offer_sdp');
            $table->json('answer_sdp')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['recipient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_sessions');
    }
};
