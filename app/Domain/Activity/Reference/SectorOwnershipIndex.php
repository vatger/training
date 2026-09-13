<?php

namespace App\Domain\Activity\Reference;

use Illuminate\Support\Facades\Cache;

/**
 * Read model over the VATGLASSES `airspace` ownership arrays.
 *
 * Every sector carries a priority-ordered list of owning position uids. This
 * index answers the questions the timeline + activity engine need:
 *   - which sectors does a uid *primarily* own (owner[0])?
 *   - what rank does a uid hold in a given sector (top-down depth)?
 *   - which sectors belong to a group?
 *
 * Cross-dataset owner references ("country/uid") are reduced to their trailing
 * uid for matching purposes.
 */
class SectorOwnershipIndex
{
    /** @var array<string,mixed>|null */
    private ?array $index = null;

    public function __construct(
        private readonly ActivityDataMirror $mirror,
        private readonly ReferenceDataRepository $reference,
    ) {}

    /**
     * Ordered owner uids for a sector (normalised, cross-dataset refs trimmed).
     *
     * @return list<string>
     */
    public function owners(string $sectorKey): array
    {
        return $this->build()['owners'][$sectorKey] ?? [];
    }

    /**
     * The full sector-key => ordered owner uids map.
     *
     * @return array<string,list<string>>
     */
    public function allOwners(): array
    {
        return $this->build()['owners'];
    }

    /** Zero-based priority of a uid in a sector, or null if it is not an owner. */
    public function rank(string $sectorKey, string $uid): ?int
    {
        $pos = array_search($uid, $this->owners($sectorKey), true);

        return $pos === false ? null : $pos;
    }

    /**
     * Sectors whose natural (index 0) owner is this uid.
     *
     * @return list<string>
     */
    public function primarySectorKeysFor(string $uid): array
    {
        return $this->build()['primary'][$uid] ?? [];
    }

    /**
     * Every sector this uid can own at some top-down depth.
     *
     * @return list<string>
     */
    public function sectorKeysOwnedBy(string $uid): array
    {
        return $this->build()['owned'][$uid] ?? [];
    }

    /**
     * @return list<string>
     */
    public function groupSectorKeys(string $groupId): array
    {
        return $this->build()['byGroup'][$groupId] ?? [];
    }

    public function flush(): void
    {
        $this->index = null;
        Cache::forget($this->cacheKey());
    }

    /**
     * @return array{
     *     owners:array<string,list<string>>,
     *     primary:array<string,list<string>>,
     *     owned:array<string,list<string>>,
     *     byGroup:array<string,list<string>>,
     * }
     */
    private function build(): array
    {
        if ($this->index !== null) {
            return $this->index;
        }

        return $this->index = Cache::rememberForever($this->cacheKey(), function (): array {
            $owners = [];
            $primary = [];
            $owned = [];
            $byGroup = [];

            foreach ($this->reference->sectors() as $key => $sector) {
                $list = array_values(array_map(
                    static fn (string $uid): string => str_contains($uid, '/')
                        ? substr($uid, strrpos($uid, '/') + 1)
                        : $uid,
                    $sector->ownerUids,
                ));

                $owners[$key] = $list;

                if ($sector->groupId !== null) {
                    $byGroup[$sector->groupId][] = $key;
                }

                foreach ($list as $rank => $uid) {
                    $owned[$uid][] = $key;

                    if ($rank === 0) {
                        $primary[$uid][] = $key;
                    }
                }
            }

            return [
                'owners' => $owners,
                'primary' => $primary,
                'owned' => $owned,
                'byGroup' => $byGroup,
            ];
        });
    }

    private function cacheKey(): string
    {
        return "activity:reference:{$this->mirror->version()}:ownership-index";
    }
}
