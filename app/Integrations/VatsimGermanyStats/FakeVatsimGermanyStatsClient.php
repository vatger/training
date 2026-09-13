<?php

namespace App\Integrations\VatsimGermanyStats;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * In-memory fake for tests and local development.
 *
 * Seed sessions per CID with {@see self::fake()}; {@see self::getSessionsInRange()}
 * returns every seeded session across all CIDs that overlaps the range.
 */
class FakeVatsimGermanyStatsClient implements VatsimGermanyStatsClientInterface
{
    /** @var array<int, list<array<string,mixed>>> */
    private array $sessions = [];

    /**
     * @param  list<array<string,mixed>>  $sessions
     */
    public function fake(int $cid, array $sessions): self
    {
        $this->sessions[$cid] = array_merge(
            $this->sessions[$cid] ?? [],
            array_map(fn (array $s): array => $s + ['account_id' => $cid], $sessions),
        );

        return $this;
    }

    public function getAtcSessions(int $cid, CarbonInterface $from, ?CarbonInterface $to = null): array
    {
        $from = Carbon::parse($from)->startOfDay();
        $to = $to ? Carbon::parse($to)->endOfDay() : Carbon::now()->endOfDay();

        return array_values(array_filter(
            $this->sessions[$cid] ?? [],
            fn (array $session): bool => $this->overlaps($session, $from, $to),
        ));
    }

    public function getSessionsInRange(CarbonInterface $from, CarbonInterface $to, array $callsignPrefixes = []): array
    {
        $from = Carbon::parse($from);
        $to = Carbon::parse($to);
        $out = [];

        foreach ($this->sessions as $sessions) {
            foreach ($sessions as $session) {
                if (! $this->overlaps($session, $from, $to)) {
                    continue;
                }

                if ($callsignPrefixes !== [] && ! $this->matchesPrefix((string) ($session['callsign'] ?? ''), $callsignPrefixes)) {
                    continue;
                }

                $out[] = $session + ['updated_at' => $session['disconnected_at'] ?? Carbon::now()->toIso8601String()];
            }
        }

        return $out;
    }

    /**
     * @param  array<string,mixed>  $session
     */
    private function overlaps(array $session, CarbonInterface $from, CarbonInterface $to): bool
    {
        if (! isset($session['connected_at'])) {
            return false;
        }

        $connectedAt = Carbon::parse($session['connected_at']);
        $disconnectedAt = isset($session['disconnected_at']) ? Carbon::parse($session['disconnected_at']) : null;

        return $connectedAt->lessThan($to)
            && ($disconnectedAt === null || $disconnectedAt->greaterThan($from));
    }

    /**
     * @param  list<string>  $prefixes
     */
    private function matchesPrefix(string $callsign, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($callsign, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
