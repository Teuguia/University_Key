<?php

namespace Tests\Feature;

use App\Models\User;
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
     * Verifie que l'inscription cree un utilisateur, hash le mot de passe et retourne un token Sanctum.
     */
    public function test_student_can_register_and_receive_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'prenom' => 'Marie',
            'nom' => 'Ngono',
            'email' => 'marie.ngono@example.com',
            'telephone' => '690232871',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'conditions_acceptees' => true,
        ]);

        $response
            ->assertCreated()
            ->assertJsonStructure(['token', 'user' => ['id', 'email', 'role', 'statut']]);

        $user = User::query()->where('email', 'marie.ngono@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('secret123', $user->password));
        $this->assertDatabaseHas('profils_etudiants', [
            'user_id' => $user->id,
            'prenom' => 'Marie',
            'nom' => 'Ngono',
        ]);
        $this->assertDatabaseCount('codes_verification', 2);
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
