<?php

namespace App\Domain\Activity\Resolver;

use App\Domain\Activity\Data\CallsignResolution;
use App\Domain\Activity\Data\StationCallsign;
use App\Domain\Activity\Reference\CallsignResolutionMap;
use App\Domain\Activity\Reference\ReferenceDataRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Maps an observed VATSIM login callsign to exactly one canonical VATGLASSES
 * position, deterministically and without ever guessing between two real
 * stations. One position has many valid login spellings
 * (EDGG_CS_CTR / EDGG_S1_CTR / EDGG__S_CTR); the frequency reported by the
 * stats API resolves most of them, infix normalisation the rest.
 *
 * Resolution cascade (first hit wins):
 *   1. Configured alias override.
 *   2. Exact datahub logon.
 *   3. Frequency match within the (prefix, suffix) candidate bucket.
 *   4. Normalised-infix match (unambiguous only).
 *   5. Subsequence / prefix scoring with a unique winner.
 *   6. Bandbox: empty infix -> group-level dynamic sectorisation.
 *   7. Unresolved.
 */
class CallsignResolver
{
    public function __construct(
        private readonly ReferenceDataRepository $reference,
        private readonly CallsignResolutionMap $map,
    ) {}

    public function resolve(string $callsign, ?int $frequencyKhz = null, ?int $facilityType = null): CallsignResolution
    {
        $raw = strtoupper(trim($callsign));

        if ($raw === '') {
            return CallsignResolution::unresolved($raw);
        }

        if ($alias = $this->resolveAlias($raw)) {
            if ($uid = $this->map->uidForLogon($alias)) {
                return CallsignResolution::position($raw, $uid, 'alias');
            }
        }

        if ($uid = $this->map->uidForLogon($raw)) {
            return CallsignResolution::position($raw, $uid, 'exact-logon');
        }

        $cs = StationCallsign::parse($raw);
        $bucket = $this->map->bucket($cs->prefix, $cs->suffix);

        if ($bucket === []) {
            return $this->fallbackToGroup($raw, $cs);
        }

        if ($frequencyKhz !== null) {
            $byFreq = array_values(array_unique(array_filter(array_map(
                static fn (array $row): ?string => $row['freqKhz'] === $frequencyKhz ? $row['uid'] : null,
                $bucket,
            ))));

            if (count($byFreq) === 1) {
                return CallsignResolution::position($raw, $byFreq[0], 'frequency');
            }
        }

        $norm = $cs->normalisedInfix();

        if ($norm !== '' && ! $this->map->normalisedInfixIsAmbiguous($cs->prefix, $cs->suffix, $norm)) {
            $byNorm = array_values(array_unique(array_filter(array_map(
                static fn (array $row): ?string => $row['norm'] === $norm ? $row['uid'] : null,
                $bucket,
            ))));

            if (count($byNorm) === 1) {
                return CallsignResolution::position($raw, $byNorm[0], 'normalised-infix');
            }
        }

        if ($norm !== '') {
            $scored = $this->scoreBySubsequence($norm, $bucket);

            if ($scored !== null) {
                return CallsignResolution::position($raw, $scored, 'subsequence');
            }
        }

        if ($cs->isBandbox()) {
            return $this->fallbackToGroup($raw, $cs);
        }

        return $this->unresolved($raw);
    }

    private function resolveAlias(string $raw): ?string
    {
        $alias = config('activity.callsign_aliases')[$raw] ?? null;

        if ($alias === null) {
            return null;
        }

        if (is_array($alias)) {
            if (isset($alias['until']) && Carbon::parse($alias['until'])->isPast()) {
                return null;
            }

            return isset($alias['logon']) ? strtoupper((string) $alias['logon']) : null;
        }

        return strtoupper((string) $alias);
    }

    private function fallbackToGroup(string $raw, StationCallsign $cs): CallsignResolution
    {
        // A bandbox login whose prefix is a group id (EDGG_CTR -> Langen).
        if ($cs->suffix === 'CTR' && array_key_exists($cs->prefix, $this->reference->groups())) {
            return CallsignResolution::group($raw, $cs->prefix, 'group-bandbox');
        }

        return $this->unresolved($raw);
    }

    /**
     * @param  list<array{norm:string,uid:?string}>  $bucket
     */
    private function scoreBySubsequence(string $norm, array $bucket): ?string
    {
        $matches = [];

        foreach ($bucket as $row) {
            if ($row['uid'] === null || $row['norm'] === '') {
                continue;
            }

            // Observed infix is a subsequence of the candidate (or vice versa) and
            // shares the first letter - covers "GIH" vs "GINH", "CSH" vs "CS".
            if ($norm[0] !== $row['norm'][0]) {
                continue;
            }

            if ($this->isSubsequence($norm, $row['norm']) || $this->isSubsequence($row['norm'], $norm)) {
                $matches[$row['uid']] = true;
            }
        }

        return count($matches) === 1 ? array_key_first($matches) : null;
    }

    private function isSubsequence(string $needle, string $haystack): bool
    {
        $i = 0;
        $len = strlen($needle);

        for ($j = 0, $hlen = strlen($haystack); $j < $hlen && $i < $len; $j++) {
            if ($needle[$i] === $haystack[$j]) {
                $i++;
            }
        }

        return $i === $len;
    }

    private function unresolved(string $raw): CallsignResolution
    {
        if (config('activity.unresolved_action') === 'log') {
            Log::info('Activity: unresolved ATC callsign', ['callsign' => $raw]);
        }

        return CallsignResolution::unresolved($raw);
    }
}
