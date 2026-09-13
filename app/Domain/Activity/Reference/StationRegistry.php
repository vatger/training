<?php

namespace App\Domain\Activity\Reference;

use App\Domain\Activity\Data\Station;
use Illuminate\Support\Facades\Cache;

/**
 * The canonical list of every VATSIM Germany login station, parsed from the
 * mirrored datahub `api/<fir>/<type>.json` files and indexed for lookup.
 *
 * datahub `abbreviation` == VATGLASSES position uid, which is how a station is
 * tied into the sector-ownership graph downstream.
 */
class StationRegistry
{
    /** @var array<string,Station>|null keyed by logon */
    private ?array $byLogon = null;

    public function __construct(
        private readonly ActivityDataMirror $mirror,
    ) {}

    /** @return array<string,Station> keyed by logon */
    public function all(): array
    {
        if ($this->byLogon !== null) {
            return $this->byLogon;
        }

        /** @var array<string,array<string,mixed>> $raw */
        $raw = Cache::rememberForever(
            "activity:reference:{$this->mirror->version()}:stations",
            fn (): array => $this->build(),
        );

        $stations = [];

        foreach ($raw as $logon => $attributes) {
            $stations[$logon] = new Station(
                logon: $attributes['logon'],
                positionUid: $attributes['positionUid'],
                frequencyKhz: $attributes['frequencyKhz'],
                fir: $attributes['fir'],
                type: $attributes['type'],
                description: $attributes['description'],
                gcapStatus: $attributes['gcapStatus'],
                gcapTrainingAirport: $attributes['gcapTrainingAirport'],
                s1Twr: $attributes['s1Twr'],
                s1Theory: $attributes['s1Theory'],
                requiredFamiliarisations: $attributes['requiredFamiliarisations'],
                relevantAirports: $attributes['relevantAirports'],
            );
        }

        return $this->byLogon = $stations;
    }

    public function find(string $logon): ?Station
    {
        return $this->all()[strtoupper(trim($logon))] ?? null;
    }

    /** @return array<string,Station> */
    public function forFir(string $fir): array
    {
        $fir = strtoupper($fir);

        return array_filter($this->all(), static fn (Station $s): bool => $s->fir === $fir);
    }

    /** @return array<string,Station> */
    public function ofType(string $type): array
    {
        $type = strtoupper($type);

        return array_filter($this->all(), static fn (Station $s): bool => $s->type === $type);
    }

    /** @return list<string> */
    public function logons(): array
    {
        return array_keys($this->all());
    }

    public function flush(): void
    {
        $this->byLogon = null;
        Cache::forget("activity:reference:{$this->mirror->version()}:stations");
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    private function build(): array
    {
        $result = [];

        foreach ($this->mirror->readAll() as $relative => $contents) {
            if (! preg_match('#^datahub/([a-z]{4})/[a-z]+\.json$#', $relative, $m)) {
                continue;
            }

            $fir = strtoupper($m[1]);
            $decoded = json_decode($contents, true);

            if (! is_array($decoded)) {
                continue;
            }

            foreach ($decoded as $entry) {
                if (! is_array($entry) || ! isset($entry['logon'])) {
                    continue;
                }

                $station = Station::fromArray($entry, $fir);

                if ($station->logon === '') {
                    continue;
                }

                // First definition wins; per-type files never overlap in practice.
                $result[$station->logon] ??= [
                    'logon' => $station->logon,
                    'positionUid' => $station->positionUid,
                    'frequencyKhz' => $station->frequencyKhz,
                    'fir' => $station->fir,
                    'type' => $station->type,
                    'description' => $station->description,
                    'gcapStatus' => $station->gcapStatus,
                    'gcapTrainingAirport' => $station->gcapTrainingAirport,
                    's1Twr' => $station->s1Twr,
                    's1Theory' => $station->s1Theory,
                    'requiredFamiliarisations' => $station->requiredFamiliarisations,
                    'relevantAirports' => $station->relevantAirports,
                ];
            }
        }

        ksort($result);

        return $result;
    }
}
