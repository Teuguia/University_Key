<?php

// Commentaire d'intention: routes API publiques et privees consommees par le frontend React.

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\CommunicationController;
use App\Http\Controllers\Api\V1\Public\EtablissementController;
use App\Http\Controllers\Api\V1\Public\RegleController;
use App\Http\Controllers\Api\V1\Public\SearchController;
use App\Http\Controllers\Api\V1\Admin\AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\OrientationTestController;
use App\Http\Controllers\Api\V1\Admin\RegleController as AdminRegleController;
use App\Http\Controllers\Api\V1\Counselor\CounselorDashboardController;
use App\Http\Controllers\Api\V1\Student\StudentDashboardController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    Route::get('/regles', [RegleController::class, 'show']);
    Route::get('/recherche', SearchController::class);
    Route::get('/etablissements/{etablissement}', [EtablissementController::class, 'show']);

    Route::prefix('auth')->group(function () {
        // Limite les tentatives publiques pour reduire le brute-force.
        Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth');
        Route::post('/verification/verify', [AuthController::class, 'verifyCode'])->middleware('throttle:verification-code');
        Route::post('/verification/resend', [AuthController::class, 'resendVerificationCode'])->middleware('throttle:verification-resend');

        // Sanctum protege les routes privees par token Bearer.
        Route::middleware(['auth:sanctum', 'can:access-active-account'])->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    // Routes privees de l'espace etudiant apres inscription/connexion.
    Route::middleware(['auth:sanctum', 'can:access-active-account'])->prefix('student')->group(function () {
        Route::get('/dashboard', [StudentDashboardController::class, 'show']);
        Route::post('/profile/photo', [StudentDashboardController::class, 'updatePhoto']);
        Route::get('/orientation-tests/{test}', [StudentDashboardController::class, 'orientationTest']);
        Route::post('/orientation-tests/{test}/submit', [StudentDashboardController::class, 'submitOrientationTest']);
    });

    // Routes privees de supervision reservees aux administrateurs.
    Route::middleware(['auth:sanctum', 'can:access-active-account'])->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'show']);
        Route::get('/regles', [AdminRegleController::class, 'show']);
        Route::patch('/regles', [AdminRegleController::class, 'update']);
        Route::get('/activity-logs', [AdminDashboardController::class, 'activityLogs']);
        Route::get('/students', [AdminDashboardController::class, 'students']);
        Route::get('/counselors', [AdminDashboardController::class, 'counselors']);
        Route::get('/pending-accounts', [AdminDashboardController::class, 'pendingAccountsList']);
        Route::get('/test-sessions', [AdminDashboardController::class, 'testSessions']);
        Route::get('/schools', [AdminDashboardController::class, 'schools']);
        Route::patch('/schools/{school}', [AdminDashboardController::class, 'updateSchool']);
        Route::patch('/users/{user}/status', [AdminDashboardController::class, 'updateAccountStatus']);
        Route::get('/orientation-tests', [OrientationTestController::class, 'index']);
        Route::post('/orientation-tests', [OrientationTestController::class, 'store']);
        Route::get('/orientation-tests/{test}', [OrientationTestController::class, 'show']);
        Route::patch('/orientation-tests/{test}', [OrientationTestController::class, 'update']);
        Route::delete('/orientation-tests/{test}', [OrientationTestController::class, 'destroy']);
    });

    // Routes privees de l'espace conseiller, accessibles apres validation du compte.
    Route::middleware(['auth:sanctum', 'can:access-active-account'])->prefix('counselor')->group(function () {
        Route::get('/dashboard', [CounselorDashboardController::class, 'show']);
    });

    // Echanges prives et appels audio WebRTC entre un etudiant et un conseiller.
    Route::middleware(['auth:sanctum', 'can:access-active-account'])->prefix('communications')->group(function () {
        Route::get('/contacts', [CommunicationController::class, 'contacts']);
        Route::get('/conversations', [CommunicationController::class, 'index']);
        Route::post('/conversations', [CommunicationController::class, 'storeConversation']);
        Route::get('/conversations/{conversation}', [CommunicationController::class, 'show']);
        Route::post('/conversations/{conversation}/messages', [CommunicationController::class, 'storeMessage']);
        Route::get('/notifications', [CommunicationController::class, 'notifications']);
        Route::patch('/notifications/read-all', [CommunicationController::class, 'markAllNotificationsRead']);
        Route::patch('/notifications/{notification}/read', [CommunicationController::class, 'markNotificationRead']);
        Route::post('/conversations/{conversation}/calls', [CommunicationController::class, 'startCall']);
        Route::get('/calls/incoming', [CommunicationController::class, 'incomingCalls']);
        Route::get('/calls/{call}', [CommunicationController::class, 'showCall']);
        Route::patch('/calls/{call}/answer', [CommunicationController::class, 'answerCall']);
        Route::patch('/calls/{call}/finish', [CommunicationController::class, 'finishCall']);
    });
});
