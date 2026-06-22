<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegleController extends Controller
{
    public function __construct(private readonly ActivityLogService $activityLog)
    {
    }

    /**
     * Retourne les documents legaux qui alimentent les boutons de consentement
     * et de confidentialite visibles sur le site public.
     */
    public function show(Request $request): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Action reservee aux administrateurs.'], 403);
        }

        $regle = DB::table('regles')->orderBy('id')->first(['conditions', 'politique', 'updated_at']);

        return response()->json([
            'data' => [
                'conditions' => $regle?->conditions ?? '',
                'politique' => $regle?->politique ?? '',
                'updated_at' => $regle?->updated_at,
            ],
        ]);
    }

    /**
     * Remplace les textes affiches aux utilisateurs et conserve une trace d'audit.
     */
    public function update(Request $request): JsonResponse
    {
        $admin = $request->user();

        if (! $admin->isAdmin()) {
            return response()->json(['message' => 'Action reservee aux administrateurs.'], 403);
        }

        $validated = $request->validate([
            'conditions' => ['required', 'string', 'min:20', 'max:100000'],
            'politique' => ['required', 'string', 'min:20', 'max:100000'],
        ]);
        $now = now();

        DB::table('regles')->updateOrInsert(
            ['id' => 1],
            [
                'conditions' => trim($validated['conditions']),
                'politique' => trim($validated['politique']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        $this->activityLog->record(
            $admin,
            'Mise a jour des documents legaux',
            'Les textes de consentement/conditions et de confidentialite ont ete mis a jour.',
            $request,
            ['target_type' => 'regles', 'target_id' => 1]
        );

        return response()->json([
            'message' => 'Les documents legaux ont ete mis a jour et sont publies.',
            'data' => [
                'conditions' => trim($validated['conditions']),
                'politique' => trim($validated['politique']),
                'updated_at' => $now,
            ],
        ]);
    }
}
