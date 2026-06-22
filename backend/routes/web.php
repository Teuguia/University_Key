<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $frontend = public_path('index.html');

    // En production, le script build-frontend copie le bundle React dans public.
    // Le fallback conserve la page Laravel pendant le developpement local sans build.
    if (is_file($frontend)) {
        return response()->file($frontend, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    return view('welcome');
});
