<?php

// Commentaire d'intention: journalise les actions sensibles de l'administration sans bloquer l'action metier.

namespace App\Services\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Enregistre les actions sensibles de l'administration dans la base de donnees.
 */
class ActivityLogService
{
    /**
     * L'echec d'audit ne doit pas annuler l'action demandee par l'administrateur.
     * La table logs_admin est creee par migration et persiste donc apres deploiement.
     */
    public function record(?User $admin, string $action, string $detail, ?Request $request = null, array $context = []): void
    {
        if (! $this->hasAuditColumns()) {
            return;
        }

        try {
            DB::table('logs_admin')->insert([
                'admin_id' => $admin?->id,
                'action' => $action,
                'cible_type' => $context['target_type'] ?? null,
                'cible_id' => $context['target_id'] ?? null,
                'adresse_ip' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'metadata' => json_encode([
                    'detail' => $detail,
                    ...$context,
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (Throwable $exception) {
            Log::warning('admin.activity_log.failed', [
                'action' => $action,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function hasAuditColumns(): bool
    {
        foreach (['admin_id', 'action', 'metadata', 'created_at', 'updated_at'] as $column) {
            if (! Schema::hasColumn('logs_admin', $column)) {
                return false;
            }
        }

        return true;
    }
}
