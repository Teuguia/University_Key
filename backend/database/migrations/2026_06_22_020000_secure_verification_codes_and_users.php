<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Les OTP ne doivent jamais rester lisibles dans la base. Les comptes
     * existants ne sont pas bloques : seul un nouveau compte public porte le
     * verrou verification_requise.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('telephone_verified_at')->nullable();
            $table->boolean('verification_requise')->default(false)->index();
        });

        Schema::table('codes_verification', function (Blueprint $table): void {
            $table->string('code', 255)->change();
            $table->index(['user_id', 'type', 'statut'], 'verification_user_type_status_index');
        });

        // Convertit egalement les codes deja presents avant le deploiement.
        DB::table('codes_verification')->orderBy('id')->chunkById(100, function ($codes): void {
            foreach ($codes as $verification) {
                DB::table('codes_verification')->where('id', $verification->id)->update([
                    'code' => Hash::make((string) $verification->code),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('codes_verification', function (Blueprint $table): void {
            $table->dropIndex('verification_user_type_status_index');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['verification_requise']);
            $table->dropColumn(['telephone_verified_at', 'verification_requise']);
        });
    }
};
