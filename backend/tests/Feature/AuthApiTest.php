<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\CodeVerification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    private bool $transactionStarted = false;

    /**
     * Ces tests utilisent PostgreSQL car pdo_sqlite n'est pas disponible dans l'environnement local.
     */
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'pgsql',
            'database.connections.pgsql.host' => '127.0.0.1',
            'database.connections.pgsql.port' => '5432',
            'database.connections.pgsql.database' => 'orientation',
            'database.connections.pgsql.username' => 'postgres',
            'database.connections.pgsql.password' => 'root',
        ]);

        DB::purge('pgsql');
        DB::connection('pgsql')->beginTransaction();
        $this->transactionStarted = true;
    }

    /**
     * Annule les donnees creees par chaque test pour garder la base locale propre.
     */
    protected function tearDown(): void
    {
        if ($this->transactionStarted) {
            DB::connection('pgsql')->rollBack();
        }

        parent::tearDown();
    }

    /**
     * L'inscription ne doit jamais donner de token avant la double verification.
     */
    public function test_student_registration_requires_double_verification_without_token(): void
    {
        $email = 'marie.'.uniqid().'@example.com';
        $telephone = '69'.random_int(1000000, 9999999);

        $response = $this->postJson('/api/v1/auth/register', [
            'role' => 'etudiant',
            'prenom' => 'Marie',
            'nom' => 'Ngono',
            'email' => $email,
            'telephone' => $telephone,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'conditions_acceptees' => true,
        ]);

        $response
            ->assertCreated()
            ->assertJsonStructure(['user' => ['id', 'email', 'role', 'statut'], 'verification']);

        $this->assertNull($response->json('token'));

        $user = User::query()->where('email', $email)->firstOrFail();

        $this->assertTrue(Hash::check('secret123', $user->password));
        $this->assertSame('en_attente', $user->statut);
        $this->assertTrue($user->verification_requise);
        $this->assertDatabaseHas('profils_etudiants', [
            'user_id' => $user->id,
            'prenom' => 'Marie',
            'nom' => 'Ngono',
        ]);
        $codes = CodeVerification::query()->where('user_id', $user->id)->get();
        $this->assertCount(2, $codes);
        $this->assertTrue($codes->every(fn (CodeVerification $code): bool => strlen($code->code) > 6));
    }

    public function test_student_receives_token_only_after_both_codes_are_verified(): void
    {
        $user = User::factory()->create([
            'email' => 'verify@example.com',
            'telephone' => '690000001',
            'role' => 'etudiant',
            'statut' => 'en_attente',
            'verification_requise' => true,
            'email_verified_at' => null,
            'telephone_verified_at' => null,
        ]);

        CodeVerification::query()->create([
            'user_id' => $user->id,
            'code' => Hash::make('111111'),
            'type' => 'email',
            'cible' => $user->email,
            'statut' => 'en_attente',
            'expire_le' => now()->addMinutes(15),
        ]);
        CodeVerification::query()->create([
            'user_id' => $user->id,
            'code' => Hash::make('222222'),
            'type' => 'telephone',
            'cible' => $user->telephone,
            'statut' => 'en_attente',
            'expire_le' => now()->addMinutes(15),
        ]);

        $this->postJson('/api/v1/auth/verification/verify', [
            'identifiant' => $user->email,
            'type' => 'email',
            'code' => '111111',
        ])->assertOk()->assertJsonPath('verification.complete', false);

        $this->assertNull($user->fresh()->telephone_verified_at);

        $response = $this->postJson('/api/v1/auth/verification/verify', [
            'identifiant' => $user->telephone,
            'type' => 'telephone',
            'code' => '222222',
            'device_name' => 'test-suite',
        ]);

        $response->assertOk()->assertJsonStructure(['token', 'user' => ['id', 'statut']]);
        $this->assertSame('actif', $user->fresh()->statut);
        $this->assertFalse($user->fresh()->verification_requise);
    }

    public function test_otp_is_invalidated_after_five_incorrect_attempts(): void
    {
        $user = User::factory()->create([
            'email' => 'attempts@example.com',
            'telephone' => '690000002',
            'role' => 'etudiant',
            'statut' => 'en_attente',
            'verification_requise' => true,
        ]);
        $verification = CodeVerification::query()->create([
            'user_id' => $user->id,
            'code' => Hash::make('333333'),
            'type' => 'email',
            'cible' => $user->email,
            'statut' => 'en_attente',
            'expire_le' => now()->addMinutes(15),
        ]);

        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/v1/auth/verification/verify', [
                'identifiant' => $user->email,
                'type' => 'email',
                'code' => '000000',
            ])->assertUnprocessable();
        }

        $this->assertSame('expire', $verification->fresh()->statut);
        $this->assertSame(5, $verification->fresh()->nb_tentatives);
    }

    /**
     * Verifie qu'un conseiller peut choisir son role et creer le profil conseiller.
     */
    public function test_counselor_can_register_with_role_and_speciality(): void
    {
        $email = 'paul.'.uniqid().'@example.com';
        $telephone = '69'.random_int(1000000, 9999999);

        $response = $this->postJson('/api/v1/auth/register', [
            'role' => 'conseiller',
            'prenom' => 'Paul',
            'nom' => 'Mvondo',
            'email' => $email,
            'telephone' => $telephone,
            'specialite' => 'Orientation scolaire et professionnelle',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'conditions_acceptees' => true,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('user.role', 'conseiller');

        $user = User::query()->where('email', $email)->firstOrFail();

        $this->assertDatabaseHas('profils_conseillers', [
            'user_id' => $user->id,
            'prenom' => 'Paul',
            'nom' => 'Mvondo',
            'specialite' => 'Orientation scolaire et professionnelle',
        ]);
    }

    /**
     * Verifie la connexion par email et l'acces a une route protegee par Sanctum.
     */
    public function test_student_can_login_and_read_profile(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'telephone' => '699999999',
            'password' => Hash::make('secret123'),
            'role' => 'etudiant',
            'statut' => 'actif',
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'login@example.com',
            'password' => 'secret123',
        ]);

        $token = $login->assertOk()->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);
    }

    /**
     * Verifie que les comptes suspendus ne peuvent pas obtenir de token actif.
     */
    public function test_suspended_user_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'blocked@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'etudiant',
            'statut' => 'suspendu',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'blocked@example.com',
            'password' => 'secret123',
        ])->assertUnprocessable();
    }
}
