<?php

namespace App\Domain\Activity\Data;

/**
 * A parsed VATSIM ATC login callsign: PREFIX[_INFIX...]_SUFFIX.
 *
 * Examples:
 *   EDGG_CToRR   -> prefix EDGG, infix "",     suffix CTR
 *   EDGG_S1_CTR  -> prefix EDGG, infix "S1",   suffix CTR
 *   EDGG__S_CTR  -> prefix EDGG, infix "S",    suffix CTR (empty segments dropped)
 *   EDDL_APP     -> prefix EDDL, infix "",     suffix APP
 *   EDDF_C_TWR   -> prefix EDDF, infix "C",    suffix TWR
 */
readonly class StationCallsign
{
    public function __construct(
        public string $raw,
        public string $prefix,
        public string $infix,
        public string $suffix,
    ) {}

    public static function parse(string $callsign): self
    {
        $raw = strtoupper(trim($callsign));
        $parts = explode('_', $raw);

        if (count($parts) < 2) {
            return new self($raw, $raw, '', '');
        }

        $prefix = array_shift($parts);
        $suffix = array_pop($parts);

        // Middle segments joined without their empty entries (handles EDGG__S_CTR).
        $infix = implode('', array_filter($parts, static fn (string $p): bool => $p !== ''));

        return new self($raw, $prefix, $infix, $suffix);
    }

    /**
     * Infix with any digits removed, e.g. "S1" -> "S", "C1H" -> "CH", "CSH1" -> "CSH".
     * Controllers append/insert digits for relief or parallel sessions; they never
     * carry meaning in the German dataset (position uids are letter-only).
     */
    public function normalisedInfix(): string
    {
        return self::normalise($this->infix);
    }

    /**
     * Upper-case, strip everything except A-Z. Digits carry no meaning in the
     * German dataset (relief / parallel-session suffixes), so "S1" -> "S",
     * "C1H" -> "CH", "CSH1" -> "CSH".
     */
    public static function normalise(string $value): string
    {
        return preg_replace('/[^A-Z]/', '', strtoupper($value)) ?? '';
    }

    /** The airport ICAO this callsign belongs to, when the prefix is an ICAO. */
    public function icao(): ?string
    {
        return preg_match('/^[A-Z]{4}$/', $this->prefix) === 1 ? $this->prefix : null;
    }

    public function isBandbox(): bool
    {
        return $this->normalisedInfix() === '';
    }
}
