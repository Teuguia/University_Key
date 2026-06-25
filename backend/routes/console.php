<?php

// Commentaire d'intention: declare les commandes console simples disponibles dans Laravel.

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('db:backup', function () {
    $connection = config('database.default');
    $config = config("database.connections.{$connection}");
    $directory = storage_path('app/backups');
    $filename = $directory.'/backup-'.now()->format('Y-m-d-His').'.sql';

    File::ensureDirectoryExists($directory);

    if ($connection !== 'pgsql') {
        $this->error('La sauvegarde automatisee est documentee pour PostgreSQL.');
        return 1;
    }

    // pg_dump doit etre installe sur le serveur et protege par les droits systeme.
    $command = sprintf(
        'set PGPASSWORD=%s&& pg_dump --host=%s --port=%s --username=%s --dbname=%s --file=%s',
        escapeshellarg((string) ($config['password'] ?? '')),
        escapeshellarg((string) $config['host']),
        escapeshellarg((string) $config['port']),
        escapeshellarg((string) $config['username']),
        escapeshellarg((string) $config['database']),
        escapeshellarg($filename),
    );

    exec($command, $output, $exitCode);

    if ($exitCode !== 0) {
        $this->error('La sauvegarde a echoue. Verifiez pg_dump, les droits et les variables DB.');
        return 1;
    }

    $this->info("Sauvegarde creee : {$filename}");
    return 0;
})->purpose('Sauvegarde la base PostgreSQL dans storage/app/backups.');
