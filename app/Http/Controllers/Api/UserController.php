<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function show(Request $request, int $vatsimId): JsonResponse
    {
        try {
            $user = User::where('vatsim_id', $vatsimId)->first();

            if (! $user) {
                Log::info('User retrieval - user not found', ['vatsim_id' => $vatsimId]);

                return response()->json(['error' => 'User not found'], 200);
            }

            return response()->json($user->toArray(), 200);

        } catch (\Exception $e) {
            Log::error('User retrieval failed', [
                'vatsim_id' => $vatsimId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to retrieve user data',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
