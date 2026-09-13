<?php

namespace App\Integrations\VatsimGermanyStats;

use Carbon\CarbonInterface;

interface VatsimGermanyStatsClientInterface
{
    /**
     * Fetch a single controller's ATC sessions (GET /api/atc/{cid}/sessions).
     *
     * @return list<array<string,mixed>>
     */
    public function getAtcSessions(int $cid, CarbonInterface $from, ?CarbonInterface $to = null): array;

    /**
     * Fetch every ATC session overlapping [$from, $to) across all controllers
     * (GET /api/atc/sessions). Pagination is walked internally.
     *
     * Selection is overlap-based server-side:
     *   connected_at < to AND (disconnected_at IS NULL OR disconnected_at > from)
     *
     * @param  list<string>  $callsignPrefixes  restrict to callsigns starting with one of these
     * @return list<array{
     *     id:int, account_id:int, callsign:string, frequency:float|null,
     *     facility_type:int|null, connected_at:string, disconnected_at:string|null,
     *     minutes_online:int|float, updated_at:string
     * }>
     */
    public function getSessionsInRange(CarbonInterface $from, CarbonInterface $to, array $callsignPrefixes = []): array;
}
