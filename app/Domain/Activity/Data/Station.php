<?php

namespace App\Domain\Activity\Data;

/**
 * A VATSIM Germany login station from the datahub registry, enriched with the
 * VATGLASSES position uid it maps to (datahub `abbreviation` == VATGLASSES uid).
 */
readonly class Station
{
    /**
     * @param  list<string>  $requiredFamiliarisations
     * @param  list<string>  $relevantAirports
     */
    public function __construct(
        public string $logon,
        public ?string $positionUid,
        public ?int $frequencyKhz,
        public string $fir,
        public string $type,
        public ?string $description,
        public ?string $gcapStatus,
        public bool $gcapTrainingAirport,
        public bool $s1Twr,
        public bool $s1Theory,
        public array $requiredFamiliarisations,
        public array $relevantAirports,
    ) {}

    /**
     * @param  array<string,mixed>  $raw  a datahub station object
     */
    public static function fromArray(array $raw, string $fir): self
    {
        $logon = strtoupper((string) ($raw['logon'] ?? ''));
        $callsign = StationCallsign::parse($logon);

        return new self(
            logon: $logon,
            positionUid: isset($raw['abbreviation']) && $raw['abbreviation'] !== ''
                ? (string) $raw['abbreviation']
                : null,
            frequencyKhz: isset($raw['frequency']) ? VgPosition::toKhz((string) $raw['frequency']) : null,
            fir: strtoupper($fir),
            type: $callsign->suffix,
            description: isset($raw['description']) ? (string) $raw['description'] : null,
            gcapStatus: isset($raw['gcap_status']) ? (string) $raw['gcap_status'] : null,
            gcapTrainingAirport: (bool) ($raw['gcap_training_airport'] ?? false),
            s1Twr: (bool) ($raw['s1_twr'] ?? false),
            s1Theory: (bool) ($raw['s1_theory'] ?? false),
            requiredFamiliarisations: array_values(array_map('strval', $raw['required_familiarisations'] ?? [])),
            relevantAirports: array_values(array_map('strtoupper', $raw['relevant_airports'] ?? [])),
        );
    }

    public function callsign(): StationCallsign
    {
        return StationCallsign::parse($this->logon);
    }

    public function isAerodrome(): bool
    {
        return in_array($this->type, ['TWR', 'GND', 'DEL', 'AFIS'], true);
    }
}
