<?php

namespace App\Domain\Activity\Data;

/**
 * A VATGLASSES `airspace` entry: a controllable ATC sector with a
 * priority-ordered ownership list. `ownerUids[0]` is the natural / primary
 * owner; later entries are top-down fallbacks used when higher-priority
 * positions are offline.
 */
readonly class VgSector
{
    /**
     * @param  list<string>  $ownerUids  owning position uids, descending priority
     * @param  list<array{min?:int,max?:int,points:array<int,array{0:string,1:string}>,runways?:array<mixed>}>  $volumes
     */
    public function __construct(
        public string $key,
        public string $id,
        public ?string $groupId,
        public array $ownerUids,
        public array $volumes,
    ) {}

    public function primaryOwnerUid(): ?string
    {
        return $this->ownerUids[0] ?? null;
    }

    /**
     * @param  array<string,mixed>  $raw
     */
    public static function fromArray(string $key, array $raw): self
    {
        return new self(
            key: $key,
            id: (string) ($raw['id'] ?? $key),
            groupId: isset($raw['group']) ? (string) $raw['group'] : null,
            ownerUids: array_values(array_map('strval', $raw['owner'] ?? [])),
            volumes: array_values($raw['sectors'] ?? []),
        );
    }
}
