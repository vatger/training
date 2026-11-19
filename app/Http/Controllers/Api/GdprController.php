<?php

namespace App\Http\Controllers\Api;

use App\Domain\Gdpr\UserDataMap;
use App\Http\Controllers\Controller;
use App\Models\GdprRemovalRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GdprController extends Controller
{
    /**
     * Polled by the VATGER homepage (roughly hourly) until it gets a 200.
     *
     * The first call opens a removal request for Leitung to review, every call until
     * that request is completed is answered with 202, which the homepage does not
     * count as success. Nothing is deleted here. Every call is recorded, a pending request
     * that is not polled for 7 days is cancelled (CancelStaleGdprRemoval).
     */
    public function delete(Request $request, int $vatsimId): JsonResponse
    {
        try {
            $removal = DB::transaction(function () use ($vatsimId) {
                $open = GdprRemovalRequest::open()->where('vatsim_id', $vatsimId)->first();

                if ($open) {
                    $open->update(['last_polled_at' => now()]);

                    return $open;
                }

                $user = User::where('vatsim_id', $vatsimId)->lockForUpdate()->first();

                if (! $user) {
                    return null;
                }

                return GdprRemovalRequest::create([
                    'user_id' => $user->id,
                    'vatsim_id' => $vatsimId,
                    'user_name' => $user->name,
                    'search_terms' => UserDataMap::searchTerms($user),
                    'last_polled_at' => now(),
                    'status' => GdprRemovalRequest::STATUS_PENDING,
                ]);
            });

            if (! $removal) {
                // Nothing stored (anymore), which is what the caller wants to hear.
                return response()->json(['error' => 'User not found'], 200);
            }

            if ($removal->wasRecentlyCreated) {
                Log::info('GDPR removal request created', ['vatsim_id' => $vatsimId, 'request_id' => $removal->id]);
            }

            return response()->json([
                'message' => 'Removal is pending review',
                'status' => $removal->status,
            ], 202);
        } catch (\Exception $e) {
            Log::error('GDPR removal request failed', ['vatsim_id' => $vatsimId, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'Failed to process removal request'], 500);
        }
    }

    /**
     * Everything this system stores about a user, as JSON.
     */
    public function show(int $vatsimId): JsonResponse
    {
        $user = User::where('vatsim_id', $vatsimId)->first();

        if (! $user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $data = UserDataMap::collect($user, full: true);

        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'user' => $data['profile'],
            'data' => collect($data['sections'])->map(fn (array $section) => [
                'description' => $section['label'],
                'kind' => $section['kind'] === 'owned' ? 'own_data' : 'written_by_user_on_other_records',
                'count' => count($section['rows']),
                'records' => $section['rows'],
            ]),
        ]);
    }
}
