<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Familiarisation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class FamiliarisationController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $familiarisations = Familiarisation::with(['user', 'sector'])
                ->get()
                ->map(function ($fam) {
                    return [
                        'vatsim_id' => $fam->user->vatsim_id,
                        'sector' => $fam->sector->name,
                        'fir' => $fam->sector->fir,
                    ];
                });

            return response()->json([
                'data' => $familiarisations
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve familiarisations', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to retrieve familiarisations',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}