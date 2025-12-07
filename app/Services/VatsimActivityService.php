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
        'EDDF' => ['EDGG_G', 'EDGG_R', 'EDGG_D', 'EDGG_B', 'EDGG_K'],
        'EDDK' => ['EDGG_P'],
        'EDDL' => ['EDGG_P'],
        'EDDM' => ['EDMM_N', 'EDMM_Z', 'EDMM_R'],
    ];

    /**
     * Get activity data for a specific endorsement - UPDATED to return both minutes and last activity date
     */
    public function getEndorsementActivity(array $endorsement): array
    {
        $vatsimId = $endorsement['user_cid'];
        $position = $endorsement['position'];
        
        $maxRetries = 3;
        $attempt = 0;
        
        while ($attempt < $maxRetries) {
            try {
                $connections = $this->getVatsimConnections($vatsimId);
                $result = $this->calculateActivity($endorsement, $connections);

                return $result;
                
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
                    // Wait 15 seconds before retry (matching Python behavior)
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

    /**
     * Get VATSIM connections for a user in the last 180 days
     */
    protected function getVatsimConnections(int $vatsimId): array
    {
        $cacheKey = "vatsim_activity:{$vatsimId}";
        
        return Cache::remember($cacheKey, now()->addHours(1), function () use ($vatsimId) {
            $start = Carbon::now()->subDays(180)->format('Y-m-d');
            $apiUrl = "https://stats.vatsim-germany.org/api/atc/{$vatsimId}/sessions/?cid={$vatsimId}&start_date={$start}";
            
            try {
                Log::debug('Fetching VATSIM activity from vatsim-germany.org', [
                    'vatsim_id' => $vatsimId,
                    'url' => $apiUrl,
                    'start_date' => $start
                ]);

                $response = Http::timeout(15) // Increased timeout
                    ->retry(2, 1000) // Retry twice with 1 second delay
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

                Log::debug('VATSIM activity fetched successfully from vatsim-germany.org', [
                    'vatsim_id' => $vatsimId,
                    'connections_count' => count($data)
                ]);

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

    /**
     * Calculate activity based on endorsement and connections
     */
    protected function calculateActivity(array $endorsement, array $connections): array
    {
        $activityMinutes = 0;
        $position = $endorsement['position'];
        $lastActivityDate = null;
        
        Log::debug('Starting activity calculation', [
            'position' => $position,
            'connections_count' => count($connections)
        ]);
        
        if (str_ends_with($position, '_CTR')) {
            // CTR position logic
            $ctrlPrefix = substr($position, 0, 6);
            
            foreach ($connections as $connection) {
                $callsign = $connection['callsign'] ?? '';
                
                if (str_starts_with($callsign, $ctrlPrefix) || 
                    ($position === 'EDWW_W_CTR' && $callsign === 'EDWW_CTR')) {
                    $minutes = floatval($connection['minutes_online'] ?? 0);
                    $activityMinutes += $minutes;

                    // Update last activity date
                    $connectionDate = $this->parseConnectionDate($connection);
                    if ($connectionDate && ($lastActivityDate === null || $connectionDate->greaterThan($lastActivityDate))) {
                        $lastActivityDate = $connectionDate;
                    }

                    Log::debug('CTR match found', [
                        'position' => $position,
                        'callsign' => $callsign,
                        'minutes' => $minutes,
                        'date' => $connectionDate?->format('Y-m-d')
                    ]);
                }
            }
        } else {
            // Airport position logic
            $parts = explode('_', $position);
            if (count($parts) >= 2) {
                $airport = $parts[0];
                $station = end($parts);
                
                $stationsToConsider = $this->ctrTopdown[$airport] ?? [];
                
                Log::debug('Airport position setup', [
                    'position' => $position,
                    'airport' => $airport,
                    'station' => $station,
                    'ctr_stations' => $stationsToConsider
                ]);
                
                foreach ($connections as $connection) {
                    $callsign = $connection['callsign'] ?? '';
                    $minutes = floatval($connection['minutes_online'] ?? 0);
                    
                    // Check CTR topdown
                    $matchesCtr = false;
                    foreach ($stationsToConsider as $ctrStation) {
                        if (str_starts_with($callsign, $ctrStation)) {
                            $matchesCtr = true;
                            break;
                        }
                    }
                    
                    // Check suffix condition
                    $matchesSuffix = $this->suffixCondition($airport, $station, $callsign);
                    
                    if ($matchesCtr || $matchesSuffix) {
                        $activityMinutes += $minutes;

                        // Update last activity date
                        $connectionDate = $this->parseConnectionDate($connection);
                        if ($connectionDate && ($lastActivityDate === null || $connectionDate->greaterThan($lastActivityDate))) {
                            $lastActivityDate = $connectionDate;
                        }

                        Log::debug('Connection match found', [
                            'position' => $position,
                            'callsign' => $callsign,
                            'minutes' => $minutes,
                            'date' => $connectionDate?->format('Y-m-d'),
                            'matches_ctr' => $matchesCtr,
                            'matches_suffix' => $matchesSuffix,
                            'total_so_far' => $activityMinutes
                        ]);
                    }
                }
            }
        }
        
        Log::debug('Activity calculation complete', [
            'position' => $position,
            'final_minutes' => $activityMinutes,
            'last_activity_date' => $lastActivityDate?->format('Y-m-d')
        ]);

        return [
            'minutes' => $activityMinutes,
            'last_activity_date' => $lastActivityDate
        ];
    }

    /**
     * Parse connection date from VATSIM API response
     */
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

        // Fallback: try other date fields that might be present
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

    /**
     * Check if callsign matches suffix condition
     */
    protected function suffixCondition(string $endorsementApt, string $endorsementStation, string $callsign): bool
    {
        $parts = explode('_', $callsign);
        if (count($parts) < 2) {
            return false;
        }
        
        $csApt = $parts[0];
        
        // Handle different callsign formats:
        // EDDL_GND, EDDL_M_GND, EDDL__GND, EDDL_APP, etc.
        $csStation = end($parts); // Get the last part (actual station)
        
        $viableSuffixes = $this->viableSuffixes[$endorsementStation] ?? [];
        
        return $csApt === $endorsementApt && in_array($csStation, $viableSuffixes);
    }

    /**
     * Get activity status based on hours and requirements
     */
    public function getActivityStatus(float $activityMinutes): string
    {
        $minRequiredMinutes = config('services.vateud.min_activity_minutes', 180); // 3 hours default
        
        if ($activityMinutes >= $minRequiredMinutes) {
            return 'active';
        } elseif ($activityMinutes >= $minRequiredMinutes * 0.5) {
            return 'warning';
        } else {
            return 'removal';
        }
    }

    /**
     * Calculate progress percentage for activity requirements
     */
    public function getActivityProgress(float $activityMinutes): float
    {
        $minRequiredMinutes = config('services.vateud.min_activity_minutes', 180);
        return min(($activityMinutes / $minRequiredMinutes) * 100, 100);
    }
}