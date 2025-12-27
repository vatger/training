<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\VatEudService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class SoloController extends Controller
{
    protected $vatEudService;

    public function __construct(VatEudService $vatEudService)
    {
        $this->vatEudService = $vatEudService;
    }

    public function index(): JsonResponse
    {
        try {
            $soloEndorsements = $this->vatEudService->getSoloEndorsements();

            return response()->json([
                'data' => $soloEndorsements
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve solo endorsements', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to retrieve solo endorsements',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}