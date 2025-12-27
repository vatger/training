<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\VatEudService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class Tier1Controller extends Controller
{
    protected $vatEudService;

    public function __construct(VatEudService $vatEudService)
    {
        $this->vatEudService = $vatEudService;
    }

    public function index(): JsonResponse
    {
        try {
            $tier1Endorsements = $this->vatEudService->getTier1Endorsements();

            return response()->json([
                'data' => $tier1Endorsements
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve tier 1 endorsements', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to retrieve tier 1 endorsements',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}