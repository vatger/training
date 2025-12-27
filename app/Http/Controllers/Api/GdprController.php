<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\VatEudService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class GdprController extends Controller
{
    protected $vatEudService;

    public function __construct(VatEudService $vatEudService)
    {
        $this->vatEudService = $vatEudService;
    }

    public function delete(Request $request, int $vatsimId): JsonResponse
    {
        try {
            $user = User::where('vatsim_id', $vatsimId)->first();

            if (!$user) {
                Log::info('GDPR deletion - user not found', ['vatsim_id' => $vatsimId]);
                return response()->json(['error' => 'User not found'], 200);
            }

            Log::info('GDPR deletion initiated', [
                'vatsim_id' => $vatsimId,
                'user_id' => $user->id,
                'user_name' => $user->name,
            ]);

            DB::beginTransaction();

            try {
                $this->vatEudService->removeRosterAndEndorsements($vatsimId);
                
                $this->deleteVisitorFromVatEUD($vatsimId);

                ActivityLogger::log(
                    'gdpr.deletion',
                    $user,
                    "GDPR deletion for user {$user->name} (VATSIM ID: {$vatsimId})",
                    [
                        'vatsim_id' => $vatsimId,
                        'user_name' => $user->name,
                        'user_email' => $user->email,
                        'ip_address' => $request->ip(),
                    ],
                    null
                );

                $user->delete();

                DB::commit();

                Log::info('GDPR deletion completed', ['vatsim_id' => $vatsimId]);

                return response()->json(['message' => 'User deleted successfully'], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('GDPR deletion failed', [
                'vatsim_id' => $vatsimId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to delete user',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    protected function deleteVisitorFromVatEUD(int $vatsimId): void
    {
        $eudToken = config('services.vateud.token');
        
        if (!$eudToken) {
            Log::warning('VatEUD token not configured, skipping VatEUD visitor deletion', [
                'vatsim_id' => $vatsimId
            ]);
            return;
        }

        try {
            Log::info('Deleting visitor from VatEUD', ['vatsim_id' => $vatsimId]);

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'X-API-KEY' => $eudToken,
                'Accept' => 'application/json',
            ])
            ->timeout(10)
            ->delete("https://core.vateud.net/api/facility/visitors/{$vatsimId}/delete");

            if ($response->successful()) {
                Log::info('Successfully deleted visitor from VatEUD', [
                    'vatsim_id' => $vatsimId,
                    'status' => $response->status(),
                ]);
            } else {
                Log::warning('VatEUD visitor deletion returned non-success status', [
                    'vatsim_id' => $vatsimId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to delete visitor from VatEUD', [
                'vatsim_id' => $vatsimId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}