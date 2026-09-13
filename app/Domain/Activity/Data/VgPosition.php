<?php

namespace App\Domain\Activity\Data;

/**
 * A VATGLASSES `positions` entry, keyed by its short uid (e.g. "GIN", "DFAN").
 * The uid equals the datahub `abbreviation` for the same station.
 */
readonly class VgPosition
{
    /**
     * @param  list<string>  $pre  valid logon prefixes (text before the first "_")
     */
    public function __construct(
        public string $uid,
        public array $pre,
        public string $type,
        public ?int $frequencyKhz,
        public ?string $callsign,
        public ?string $groupId,
    ) {}

    /**
     * @param  array<string,mixed>  $raw
     */
    public static function fromArray(string $uid, array $raw): self
    {
        return new self(
            uid: $uid,
            pre: array_values(array_map('strtoupper', (array) ($raw['pre'] ?? []))),
            type: strtoupper((string) ($raw['type'] ?? '')),
            frequencyKhz: isset($raw['frequency']) ? self::toKhz((string) $raw['frequency']) : null,
            callsign: isset($raw['callsign']) ? (string) $raw['callsign'] : null,
            groupId: isset($raw['group']) ? (string) $raw['group'] : null,
        );
    }

    /** Normalise "136.480" / "136.48" / 136.48 to an integer kHz value (136480). */
    public static function toKhz(string|float|int|null $frequency): ?int
    {
        if ($frequency === null || $frequency === '') {
            return null;
        }

        return (int) round(((float) $frequency) * 1000);
    }
}
