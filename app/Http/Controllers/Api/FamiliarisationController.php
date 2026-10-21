<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Familiarisation;
use App\Models\Subfamiliarisation;
use App\Services\SubfamiliarisationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class FamiliarisationController extends Controller
{
    public function __construct(
        private readonly SubfamiliarisationService $subfamiliarisationService,
    ) {}

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
                'data' => $familiarisations,
                'subfamiliarisation_qualifications' => $this->getSubfamiliarisationQualifications(),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve familiarisations', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to retrieve familiarisations',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function getSubfamiliarisationQualifications(): array
    {
        $qualifications = [];

        foreach (Subfamiliarisation::with(['primarySector', 'secondarySector'])->get() as $subfamiliarisation) {
            $candidates = Familiarisation::with('user')
                ->whereIn('familiarisation_sector_id', $subfamiliarisation->sectorIds())
                ->get()
                ->groupBy('user_id')
                ->filter(fn ($fams) => $fams->count() === 1);

            foreach ($candidates as $fams) {
                $user = $fams->first()->user;

                if (! $this->subfamiliarisationService->qualifiesViaSubfamiliarisation($user, $subfamiliarisation)) {
                    continue;
                }

                $grantedSector = $subfamiliarisation->primary_sector_id === $fams->first()->familiarisation_sector_id
                    ? $subfamiliarisation->secondarySector
                    : $subfamiliarisation->primarySector;

                $qualifications[] = [
                    'vatsim_id' => $user->vatsim_id,
                    'subfamiliarisation' => $subfamiliarisation->name,
                    'granted_sector' => $grantedSector->name,
                    'granted_fir' => $grantedSector->fir,
                ];
            }
        }

        return $qualifications;
    }
}
