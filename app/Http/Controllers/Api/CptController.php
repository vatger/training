<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cpt;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CptController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $cpts = Cpt::with(['trainee', 'examiner', 'local', 'course'])
                ->where('date', '>', now())
                ->whereNull('passed')
                ->orderBy('date')
                ->get()
                ->map(function ($cpt) {
                    return [
                        'id' => $cpt->id,
                        'trainee_vatsim_id' => $cpt->trainee->vatsim_id,
                        'trainee_name' => $cpt->trainee->full_name,
                        'examiner_vatsim_id' => $cpt->examiner?->vatsim_id,
                        'examiner_name' => $cpt->examiner?->full_name,
                        'local_vatsim_id' => $cpt->local?->vatsim_id,
                        'local_name' => $cpt->local?->full_name,
                        'course_name' => $cpt->course->name,
                        'position' => $cpt->course->solo_station,
                        'date' => $cpt->date->toIso8601String(),
                        'confirmed' => $cpt->confirmed,
                    ];
                });

            return response()->json([
                'data' => $cpts
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve CPTs', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to retrieve CPTs',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}