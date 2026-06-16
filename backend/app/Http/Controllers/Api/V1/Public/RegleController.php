<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class RegleController extends Controller
{
    /**
     * Retourne les textes legaux visibles par les visiteurs.
     */
    public function show(): JsonResponse
    {
        $regle = DB::table('regles')->first(['conditions', 'politique']);

        return response()->json([
            'data' => [
                'conditions' => $regle?->conditions ?? '',
                'politique' => $regle?->politique ?? '',
            ],
        ]);
    }
}
