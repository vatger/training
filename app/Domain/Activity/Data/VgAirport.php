<?php

namespace App\Domain\Activity\Data;

/**
 * A VATGLASSES `airports` entry. `topdownUids` is the priority-ordered list of
 * positions providing top-down service above TWR. `major` chains to another
 * airport's ownership order when this one is exhausted.
 */
readonly class VgAirport
{
    /**
     * @param  list<string>  $topdownUids
     * @param  array{0:float,1:float}|null  $coord
     */
    public function __construct(
        public string $icao,
        public array $topdownUids,
        public ?string $major,
        public bool $defaultAppDep,
        public ?array $coord,
        public ?string $callsign,
    ) {}

    /**
     * @param  array<string,mixed>  $raw
     */
    public static function fromArray(string $icao, array $raw): self
    {
        $coord = null;
        if (isset($raw['coord']) && is_array($raw['coord']) && count($raw['coord']) === 2) {
            $coord = [(float) $raw['coord'][0], (float) $raw['coord'][1]];
        }

        return new self(
            icao: strtoupper($icao),
            topdownUids: array_values(array_map('strval', $raw['topdown'] ?? [])),
            major: isset($raw['major']) ? strtoupper((string) $raw['major']) : null,
            defaultAppDep: (bool) ($raw['default'] ?? true),
            coord: $coord,
            callsign: isset($raw['callsign']) ? (string) $raw['callsign'] : null,
        );
    }
}
