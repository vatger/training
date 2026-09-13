<?php

namespace App\Domain\Activity\Data;

/**
 * Outcome of resolving an observed login callsign against the reference data.
 *
 * - `positionUid` set  -> resolved to one VATGLASSES position.
 * - `groupId` set      -> a dynamic-sectorisation bandbox login covering a whole
 *                         group; it owns any group sector nobody more specific holds.
 * - `unresolved`       -> could not be mapped; contributes nothing but is recorded.
 */
readonly class CallsignResolution
{
    private function __construct(
        public string $raw,
        public ?string $positionUid,
        public ?string $groupId,
        public bool $unresolved,
        public string $via,
    ) {}

    public static function position(string $raw, string $uid, string $via): self
    {
        return new self($raw, $uid, null, false, $via);
    }

    public static function group(string $raw, string $groupId, string $via): self
    {
        return new self($raw, null, $groupId, false, $via);
    }

    public static function unresolved(string $raw): self
    {
        return new self($raw, null, null, true, 'unresolved');
    }

    public function isResolved(): bool
    {
        return ! $this->unresolved;
    }
}
