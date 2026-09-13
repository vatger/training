<?php

namespace App\Domain\Activity\Reference;

use App\Domain\Activity\Data\StationCallsign;
use App\Domain\Activity\Resolver\CallsignResolver;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Precomputed lookup tables the {@see CallsignResolver}
 * builds on: exact datahub logon -> uid, and per-(prefix,suffix) buckets of
 * candidate stations keyed by normalised infix.
 *
 * At build time every normalised infix within a bucket must map to exactly one
 * uid. A collision means two canonical stations are indistinguishable once
 * digits are stripped - it is reported (and, when
 * activity.unresolved_action = "throw", fails the rebuild) rather than being
 * allowed to silently mis-attribute activity.
 */
class CallsignResolutionMap
{
    /** @var array<string,mixed>|null */
    private ?array $data = null;

    public function __construct(
        private readonly ActivityDataMirror $mirror,
        private readonly StationRegistry $stations,
        private readonly ReferenceDataRepository $reference,
    ) {}

    public function uidForLogon(string $logon): ?string
    {
        return $this->build()['byLogon'][strtoupper(trim($logon))] ?? null;
    }

    /**
     * @return list<array{logon:string,infix:string,norm:string,uid:?string,freqKhz:?int}>
     */
    public function bucket(string $prefix, string $suffix): array
    {
        return $this->build()['buckets'][strtoupper($prefix).'|'.strtoupper($suffix)] ?? [];
    }

    public function normalisedInfixIsAmbiguous(string $prefix, string $suffix, string $norm): bool
    {
        $key = strtoupper($prefix).'|'.strtoupper($suffix).'|'.$norm;

        return isset($this->build()['collisions'][$key]);
    }

    /**
     * @return list<string>
     */
    public function collisions(): array
    {
        return array_keys($this->build()['collisions']);
    }

    public function flush(): void
    {
        $this->data = null;
        Cache::forget($this->cacheKey());
    }

    /**
     * @return array{byLogon:array<string,?string>,buckets:array<string,list<array<string,mixed>>>,collisions:array<string,true>}
     */
    private function build(): array
    {
        if ($this->data !== null) {
            return $this->data;
        }

        return $this->data = Cache::rememberForever($this->cacheKey(), function (): array {
            $byLogon = [];
            $buckets = [];
            /** @var array<string,array<string,string>> $normSeen bucketKey => norm => uid */
            $normSeen = [];
            $collisions = [];

            foreach ($this->stations->all() as $station) {
                $cs = $station->callsign();
                $bucketKey = $cs->prefix.'|'.$cs->suffix;
                $uid = $station->positionUid;

                $byLogon[$station->logon] = $uid;

                $norm = $cs->normalisedInfix();
                $buckets[$bucketKey][] = [
                    'logon' => $station->logon,
                    'infix' => $cs->infix,
                    'norm' => $norm,
                    'uid' => $uid,
                    'freqKhz' => $station->frequencyKhz,
                ];

                if ($norm === '' || $uid === null) {
                    continue;
                }

                if (isset($normSeen[$bucketKey][$norm]) && $normSeen[$bucketKey][$norm] !== $uid) {
                    $collisions[$bucketKey.'|'.$norm] = true;
                } else {
                    $normSeen[$bucketKey][$norm] = $uid;
                }
            }

            // Also index VATGLASSES position uids that have no datahub station,
            // so uid-form logins (EDGG_GIN_CTR style) still resolve.
            foreach ($this->reference->positions() as $uid => $position) {
                foreach ($position->pre as $prefix) {
                    $bucketKey = $prefix.'|'.$position->type;
                    $norm = StationCallsign::normalise($uid);

                    $alreadyListed = false;
                    foreach ($buckets[$bucketKey] ?? [] as $row) {
                        if ($row['uid'] === $uid) {
                            $alreadyListed = true;
                            break;
                        }
                    }

                    if ($alreadyListed) {
                        continue;
                    }

                    $buckets[$bucketKey][] = [
                        'logon' => "{$prefix}_{$uid}_{$position->type}",
                        'infix' => $uid,
                        'norm' => $norm,
                        'uid' => $uid,
                        'freqKhz' => $position->frequencyKhz,
                    ];

                    if (isset($normSeen[$bucketKey][$norm]) && $normSeen[$bucketKey][$norm] !== $uid) {
                        $collisions[$bucketKey.'|'.$norm] = true;
                    } else {
                        $normSeen[$bucketKey][$norm] = $uid;
                    }
                }
            }

            if ($collisions !== [] && config('activity.unresolved_action') === 'throw') {
                throw new RuntimeException(
                    'Ambiguous callsign normalisations: '.implode(', ', array_keys($collisions)),
                );
            }

            return ['byLogon' => $byLogon, 'buckets' => $buckets, 'collisions' => $collisions];
        });
    }

    private function cacheKey(): string
    {
        return "activity:reference:{$this->mirror->version()}:resolver-map";
    }
}
