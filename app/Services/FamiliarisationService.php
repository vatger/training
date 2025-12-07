<?php

namespace App\Services;

use App\Models\Familiarisation;
use App\Models\FamiliarisationSector;
use Illuminate\Support\Collection;

class FamiliarisationService
{
    /**
     * Get familiarisations for a user, grouped by FIR
     */
    public function getFamiliarisations(int $vatsimId): array
    {
        $familiarisations = Familiarisation::with('sector')
            ->whereHas('user', function ($query) use ($vatsimId) {
                $query->where('vatsim_id', $vatsimId);
            })
            ->get();

        // Group by FIR and sort
        $grouped = $familiarisations->groupBy('sector.fir');
        
        $result = [];
        foreach ($grouped as $fir => $fams) {
            $result[$fir] = $fams->sortBy('sector.name')->values()->all();
        }

        // Sort FIRs alphabetically
        ksort($result);

        return $result;
    }

    /**
     * Add a familiarisation for a user
     */
    public function addFamiliarisation(int $vatsimId, int $sectorId): bool
    {
        try {
            $user = \App\Models\User::where('vatsim_id', $vatsimId)->firstOrFail();
            
            Familiarisation::firstOrCreate([
                'user_id' => $user->id,
                'familiarisation_sector_id' => $sectorId,
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to add familiarisation', [
                'vatsim_id' => $vatsimId,
                'sector_id' => $sectorId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Remove a familiarisation for a user
     */
    public function removeFamiliarisation(int $vatsimId, int $sectorId): bool
    {
        try {
            $user = \App\Models\User::where('vatsim_id', $vatsimId)->firstOrFail();
            
            Familiarisation::where('user_id', $user->id)
                ->where('familiarisation_sector_id', $sectorId)
                ->delete();

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to remove familiarisation', [
                'vatsim_id' => $vatsimId,
                'sector_id' => $sectorId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}