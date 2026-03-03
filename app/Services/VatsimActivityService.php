<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class VatsimActivityService
{
    protected $viableSuffixes = [
        'APP' => ['APP', 'DEP'],
        'TWR' => ['APP', 'DEP', 'TWR'],
        'GNDDEL' => ['APP', 'DEP', 'TWR', 'GND', 'DEL'],
    ];

    protected $ctrTopdown = [
        'EDDB' => ['EDWW_F', 'EDWW_B', 'EDWW_K', 'EDWW_M', 'EDWW_C'],
        'EDDH' => ['EDWW_H', 'EDWW_A', 'EDWW_W', 'EDWW_C'],
        'EDDF' => ['EDGG_R', 'EDGG_D', 'EDGG_C', 'EDGG_S', 'EDGG_CA'],
        'EDDK' => ['EDGG_N', 'EDGG_NH'],
        'EDDL' => ['EDGG_N', 'EDGG_NH'],
        'EDDM' => ['EDMM_N', 'EDMM_Z', 'EDMM_R'],
    ];

    // Legacy sectors active before the EDGG restructure (March 2026).
    // Counted alongside current sectors until the transition period ends.
    protected $legacyCtrTopdown = [
        'EDDF' => ['EDGG_G', 'EDGG_R', 'EDGG_D', 'EDGG_B', 'EDGG_K'],
        'EDDK' => ['EDGG_P'],
        'EDDL' => ['EDGG_P'],
    ];

    // Combined APP sectors introduced in the EDGG restructure.
    protected $appTopdown = [
        'EDDK' => ['EDGG_KL'],
        'EDDL' => ['EDGG_KL'],
    ];

    protected $transitionEndDate = '2026-07-22';

    public function getEndorsementActivity(array $endorsement): array
    {
        $vatsimId = $endorsement['user_cid'];
        $position = $endorsement['position'];
        
        $maxRetries = 3;
        $attempt = 0;
        
        while ($attempt < $maxRetries) {
            try {
                $connections = $this->getVatsimConnections($vatsimId);
                return $this->calculateActivity($endorsement, $connections);
            } catch (\Exception $e) {
                $attempt++;
                
                Log::warning('VATSIM API attempt failed', [
                    'vatsim_id' => $vatsimId,
                    'position' => $position,
                    'attempt' => $attempt,
                    'max_retries' => $maxRetries,
                    'error' => $e->getMessage()
                ]);
                
                if ($attempt < $maxRetries) {
                    Log::info('Waiting 15 seconds before retry...');
                    sleep(15);
                } else {
                    Log::error('All VATSIM API attempts failed', [
                        'vatsim_id' => $vatsimId,
                        'position' => $position,
                        'final_error' => $e->getMessage()
                    ]);
                    return [
                        'minutes' => 0.0,
                        'last_activity_date' => null
                    ];
                }
            }
        }

        return [
            'minutes' => 0.0,
            'last_activity_date' => null
        ];
    }

    protected function getVatsimConnections(int $vatsimId): array
    {
        $cacheKey = "vatsim_activity:{$vatsimId}";
        
        return Cache::remember($cacheKey, now()->addHours(1), function () use ($vatsimId) {
            $start = Carbon::now()->subDays(180)->format('Y-m-d');
            $apiUrl = "http://stats.vatsim-germany.org/api/atc/{$vatsimId}/sessions/?start_date={$start}";
            
            try {
                $response = Http::timeout(15)
                    ->retry(2, 1000)
                    ->get($apiUrl);
                
                if (!$response->successful()) {
                    Log::warning('VATSIM Germany API request failed', [
                        'vatsim_id' => $vatsimId,
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                    return [];
                }

                $data = $response->json();
                if (!is_array($data)) {
                    Log::warning('Unexpected VATSIM Germany API response format', [
                        'vatsim_id' => $vatsimId,
                        'data_type' => gettype($data)
                    ]);
                    return [];
                }

                return $data;
                
            } catch (\Exception $e) {
                Log::error('Error fetching VATSIM connections from vatsim-germany.org', [
                    'vatsim_id' => $vatsimId,
                    'error' => $e->getMessage(),
                    'url' => $apiUrl
                ]);
                return [];
            }
        });
    }

    protected function calculateActivity(array $endorsement, array $connections): array
    {
        $activityMinutes = 0;
        $position = $endorsement['position'];
        $lastActivityDate = null;
        $inTransition = Carbon::now()->lessThan(Carbon::parse($this->transitionEndDate));
        
        if (str_ends_with($position, '_CTR')) {
            $ctrlPrefix = substr($position, 0, 6);
            
            foreach ($connections as $connection) {
                $callsign = $connection['callsign'] ?? '';
                
                if (str_starts_with($callsign, $ctrlPrefix) || 
                    ($position === 'EDWW_W_CTR' && $callsign === 'EDWW_CTR')) {
                    $minutes = floatval($connection['minutes_online'] ?? 0);
                    $activityMinutes += $minutes;

                    $connectionDate = $this->parseConnectionDate($connection);
                    if ($connectionDate && ($lastActivityDate === null || $connectionDate->greaterThan($lastActivityDate))) {
                        $lastActivityDate = $connectionDate;
                    }
                }
            }
        } else {
            $parts = explode('_', $position);
            if (count($parts) >= 2) {
                $airport = $parts[0];
                $station = end($parts);

                $ctrStations = $this->ctrTopdown[$airport] ?? [];
                $legacyCtrStations = $inTransition ? ($this->legacyCtrTopdown[$airport] ?? []) : [];
                $appStations = $this->appTopdown[$airport] ?? [];

                $ctrAllowedStations = ['APP', 'TWR', 'GNDDEL'];

                foreach ($connections as $connection) {
                    $callsign = $connection['callsign'] ?? '';
                    $minutes = floatval($connection['minutes_online'] ?? 0);

                    $matchesSuffix = $this->suffixCondition($airport, $station, $callsign);

                    $matchesCtr = false;
                    if (in_array($station, $ctrAllowedStations, true)) {
                        $allCtrStations = array_unique(array_merge($ctrStations, $legacyCtrStations));
                        foreach ($allCtrStations as $ctrStation) {
                            if (str_starts_with($callsign, $ctrStation)) {
                                $matchesCtr = true;
                                break;
                            }
                        }

                        // APP combined sectors cover TWR and GNDDEL topdown as well
                        if (!$matchesCtr && $station !== 'APP') {
                            foreach ($appStations as $appStation) {
                                if (str_starts_with($callsign, $appStation)) {
                                    $matchesCtr = true;
                                    break;
                                }
                            }
                        }
                    }

                    // APP endorsement: also match combined APP sectors
                    if (!$matchesCtr && $station === 'APP') {
                        foreach ($appStations as $appStation) {
                            if (str_starts_with($callsign, $appStation)) {
                                $matchesCtr = true;
                                break;
                            }
                        }
                    }

                    if (!$matchesCtr && !$matchesSuffix) {
                        continue;
                    }

                    $activityMinutes += $minutes;

                    $connectionDate = $this->parseConnectionDate($connection);
                    if ($connectionDate && ($lastActivityDate === null || $connectionDate->greaterThan($lastActivityDate))) {
                        $lastActivityDate = $connectionDate;
                    }
                }
            }
        }

        return [
            'minutes' => $activityMinutes,
            'last_activity_date' => $lastActivityDate
        ];
    }

    protected function parseConnectionDate(array $connection): ?Carbon
    {
        if (isset($connection['disconnected_at'])) {
            try {
                return Carbon::parse($connection['disconnected_at']);
            } catch (\Exception $e) {
                Log::warning('Failed to parse disconnected_at date', [
                    'disconnected_at' => $connection['disconnected_at'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        foreach (['connected_at', 'start', 'end', 'created_at', 'date'] as $dateField) {
            if (isset($connection[$dateField])) {
                try {
                    return Carbon::parse($connection[$dateField]);
                } catch (\Exception $e) {
                    // Continue to next field
                }
            }
        }

        return null;
    }

    protected function suffixCondition(string $endorsementApt, string $endorsementStation, string $callsign): bool
    {
        if (!str_starts_with($callsign, $endorsementApt . '_')) {
            return false;
        }

        $parts = explode('_', $callsign);
        $csStation = end($parts);
        $viableSuffixes = $this->viableSuffixes[$endorsementStation] ?? [];

        return in_array($csStation, $viableSuffixes, true);
    }

    public function getActivityStatus(float $activityMinutes): string
    {
        $minRequiredMinutes = config('services.vateud.min_activity_minutes', 180);
        
        if ($activityMinutes >= $minRequiredMinutes) {
            return 'active';
        } elseif ($activityMinutes >= $minRequiredMinutes * 0.5) {
            return 'warning';
        } else {
            return 'removal';
        }
    }

    public function getActivityProgress(float $activityMinutes): float
    {
        $minRequiredMinutes = config('services.vateud.min_activity_minutes', 180);
        return min(($activityMinutes / $minRequiredMinutes) * 100, 100);
    }
}
