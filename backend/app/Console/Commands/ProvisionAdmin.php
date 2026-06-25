<?php

// Commentaire d'intention: cree ou met a jour un compte administrateur depuis la ligne de commande.

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class ProvisionAdmin extends Command
{
    /**
     * La commande est volontairement interactive : aucun mot de passe ne doit
     * figurer dans le code, les seeders ou l'historique du shell.
     */
    protected $signature = 'admin:provision
                            {--email= : Adresse e-mail du nouvel administrateur}
                            {--name= : Nom affiche du nouvel administrateur}';

    protected $description = 'Cree une fois un administrateur sans secret code en dur';

    public function handle(): int
    {
        $email = (string) ($this->option('email') ?: $this->ask('Adresse e-mail administrateur'));
        $name = (string) ($this->option('name') ?: $this->ask('Nom affiche', 'Administrateur'));

        $validator = Validator::make([
            'email' => $email,
            'name' => $name,
        ], [
            'email' => ['required', 'email:rfc', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->error('Un compte utilise deja cette adresse e-mail. Aucun compte existant n’a ete modifie.');

            return self::FAILURE;
        }

        $password = (string) $this->secret('Mot de passe administrateur');
        $confirmation = (string) $this->secret('Confirmez le mot de passe');

        if ($password !== $confirmation) {
            $this->error('Les mots de passe ne correspondent pas.');

            return self::FAILURE;
        }

        $passwordValidator = Validator::make(['password' => $password], [
            'password' => [
                'required',
                Password::min(12)->mixedCase()->numbers()->symbols(),
            ],
        ]);

        if ($passwordValidator->fails()) {
            foreach ($passwordValidator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'admin',
            'statut' => 'actif',
            'langue_preferee' => 'fr',
            'email_verified_at' => now(),
        ]);

        $this->info('Administrateur cree. Conservez le mot de passe dans un gestionnaire de secrets.');

        return self::SUCCESS;
    }
}
