<?php

namespace App\Domain\Activity\Reference;

use App\Domain\Activity\Data\VgAirport;
use App\Domain\Activity\Data\VgPosition;
use App\Domain\Activity\Data\VgSector;
use Illuminate\Support\Facades\Cache;

/**
 * Parses the mirrored VATGLASSES data into indexed value objects.
 *
 * Supports both the single-file shape (data/ed.json with top-level `airspace`,
 * `positions`, `airports`, `groups`, `callsigns`) and the split shape
 * (airspace.json + positions.json), by merging matching top-level keys across
 * every mirrored `vatglasses/*.json` file.
 *
 * Results are cached forever under a key stamped with the current data version;
 * a data change produces a new version and the old cache entries are ignored.
 */
class ReferenceDataRepository
{
    /** @var array<string,mixed>|null */
    private ?array $parsed = null;

    public function __construct(
        private readonly ActivityDataMirror $mirror,
    ) {}

    public function version(): string
    {
        return $this->mirror->version();
    }

    /** @return array<string,VgPosition> keyed by uid */
    public function positions(): array
    {
        return $this->parse()['positions'];
    }

    public function position(string $uid): ?VgPosition
    {
        return $this->positions()[$uid] ?? null;
    }

    /** @return array<string,VgSector> keyed by sector key */
    public function sectors(): array
    {
        return $this->parse()['sectors'];
    }

    /** @return array<string,VgAirport> keyed by ICAO */
    public function airports(): array
    {
        return $this->parse()['airports'];
    }

    /** @return array<string,string> group id => display name */
    public function groups(): array
    {
        return $this->parse()['groups'];
    }

    /**
     * The VATGLASSES `callsigns` block: suffix => (middle => voice callsign).
     *
     * @return array<string,array<string,string>>
     */
    public function callsignTemplates(): array
    {
        return $this->parse()['callsigns'];
    }

    public function flush(): void
    {
        $this->parsed = null;
        Cache::forget($this->cacheKey());
    }

    /**
     * @return array{
     *     positions:array<string,VgPosition>,
     *     sectors:array<string,VgSector>,
     *     airports:array<string,VgAirport>,
     *     groups:array<string,string>,
     *     callsigns:array<string,array<string,string>>,
     * }
     */
    private function parse(): array
    {
        if ($this->parsed !== null) {
            return $this->parsed;
        }

        return $this->parsed = Cache::rememberForever(
            $this->cacheKey(),
            fn (): array => $this->build(),
        );
    }

    private function cacheKey(): string
    {
        return "activity:reference:{$this->version()}:vatglasses";
    }

    /**
     * @return array<string,mixed>
     */
    private function build(): array
    {
        $merged = ['airspace' => [], 'positions' => [], 'airports' => [], 'groups' => [], 'callsigns' => []];

        foreach ($this->mirror->readAll() as $relative => $contents) {
            if (! str_starts_with($relative, 'vatglasses/')) {
                continue;
            }

            $data = json_decode($contents, true);

            if (! is_array($data)) {
                continue;
            }

            foreach (array_keys($merged) as $key) {
                if (! isset($data[$key]) || ! is_array($data[$key])) {
                    continue;
                }

                $merged[$key] = $this->mergeSection($key, $merged[$key], $data[$key]);
            }
        }

        return [
            'positions' => $this->buildPositions($merged['positions']),
            'sectors' => $this->buildSectors($merged['airspace']),
            'airports' => $this->buildAirports($merged['airports']),
            'groups' => $this->buildGroups($merged['groups']),
            'callsigns' => $this->normaliseCallsigns($merged['callsigns']),
        ];
    }

    /**
     * @param  array<mixed>  $existing
     * @param  array<mixed>  $incoming
     * @return array<mixed>
     */
    private function mergeSection(string $key, array $existing, array $incoming): array
    {
        if ($key === 'airspace' && array_is_list($incoming)) {
            // ed.json shape: list of sector objects. Key by uid (fallback id).
            foreach ($incoming as $sector) {
                if (! is_array($sector)) {
                    continue;
                }

                $sectorKey = (string) ($sector['uid'] ?? $sector['id'] ?? count($existing));
                $existing[$sectorKey] = $sector;
            }

            return $existing;
        }

        return array_replace($existing, $incoming);
    }

    /**
     * @param  array<string,mixed>  $raw
     * @return array<string,VgPosition>
     */
    private function buildPositions(array $raw): array
    {
        $positions = [];

        foreach ($raw as $uid => $entry) {
            if (is_array($entry)) {
                $positions[(string) $uid] = VgPosition::fromArray((string) $uid, $entry);
            }
        }

        return $positions;
    }

    /**
     * @param  array<string,mixed>  $raw
     * @return array<string,VgSector>
     */
    private function buildSectors(array $raw): array
    {
        $sectors = [];

        foreach ($raw as $key => $entry) {
            if (is_array($entry)) {
                $sectors[(string) $key] = VgSector::fromArray((string) $key, $entry);
            }
        }

        return $sectors;
    }

    /**
     * @param  array<string,mixed>  $raw
     * @return array<string,VgAirport>
     */
    private function buildAirports(array $raw): array
    {
        $airports = [];

        foreach ($raw as $icao => $entry) {
            if (is_array($entry)) {
                $airports[strtoupper((string) $icao)] = VgAirport::fromArray((string) $icao, $entry);
            }
        }

        return $airports;
    }

    /**
     * @param  array<string,mixed>  $raw
     * @return array<string,string>
     */
    private function buildGroups(array $raw): array
    {
        $groups = [];

        foreach ($raw as $id => $entry) {
            $groups[(string) $id] = is_array($entry)
                ? (string) ($entry['name'] ?? $id)
                : (string) $entry;
        }

        return $groups;
    }

    /**
     * @param  array<string,mixed>  $raw
     * @return array<string,array<string,string>>
     */
    private function normaliseCallsigns(array $raw): array
    {
        $result = [];

        foreach ($raw as $suffix => $middles) {
            if (! is_array($middles)) {
                continue;
            }

            $result[strtoupper((string) $suffix)] = array_map('strval', $middles);
        }

        return $result;
    }
}
