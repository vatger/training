<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function show(Request $request, int $vatsimId): JsonResponse
    {
        try {
            $user = User::where('vatsim_id', $vatsimId)->first();

            if (!$user) {
                Log::info('User retrieval - user not found', ['vatsim_id' => $vatsimId]);
                return response()->json(['error' => 'User not found'], 200);
            }

            $userData = [];
            
            foreach ($user->getAttributes() as $key => $value) {
                $userData[$key] = $value;
            }

            return response()->json($userData, 200);

        } catch (\Exception $e) {
            Log::error('User retrieval failed', [
                'vatsim_id' => $vatsimId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to retrieve user data',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}