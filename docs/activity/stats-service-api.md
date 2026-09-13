# stats.vatsim-germany.org — session API used by the Controller Activity Engine

**App side:** `vatger/training`, `app/Domain/Activity`.
**Status:** ✅ implemented on the stats side (2026-08, not yet deployed). The Training Centre
app has been rewired to use it — there is **no local session mirror** any more.

---

## The endpoint

```
GET /api/atc/sessions
```

- Merges `statistics_atc` (archive) + `networkdata_atc` (live / still-open).
- **Overlap** selection: `connected_at < end AND (disconnected_at IS NULL OR disconnected_at > start)`.
- Keyset pagination on `(connected_at, id, source-rank)`; opaque base64-JSON `cursor`.
- Envelope `{ "data": [...], "next_cursor": "..."|null }`. `per_page` default 1000, max 5000 (422 over).
- Params: `start_date` (required, `Y-m-d` or ISO), `end_date` (exclusive upper bound),
  `updated_since`, `callsign_prefix` (comma form `ED,ET` **or** `callsign_prefix[]=` — plain repeated
  params don't survive PHP).
- `updated_at` per row = `disconnected_at` for archived rows, `now()` for still-open;
  `updated_since` filters on that proxy.
- Server caches fully-historical windows (`end_date < now − 1 day`) for 1 h; recent/live windows always
  hit the DB.
- Row shape: `id, account_id, callsign, frequency, qualification_id, facility_type, connected_at,
  disconnected_at, minutes_online, updated_at`. Timestamps UTC.

The per-CID endpoint `GET /api/atc/{cid}/sessions` is unchanged by default (bare newest-first array)
but now also emits `updated_at` and accepts `cursor`/`per_page` to opt into the same paginated engine.

## Confirmed constraints (answers to the original spec questions)

| # | Answer | App-side consequence |
|---|--------|----------------------|
| 1 | Archived rows are write-once; live rows mutate for ~15 min. | Cache days older than `volatile_days` (2) for 60 days; recent days 30 min. |
| 2 | History starts **2025-10-12** (~10 months), no pruning. | Range fetches clamp to `activity.sources.sessions.history_since`. "Eligible since" can't see further back yet. |
| 3 | `frequency` nullable, set when the datafeed provides it, no backfill, no hard cutoff. | Resolver frequency step is best-effort; infix normalisation covers the gaps. |
| 4 | UTC everywhere, no DST columns. | — |
| 5 | No auth / no throttle today. | `retries`, modest `timeout`; add a token later if throttling arrives. |
| 6 | `facility_type` 1=FSS 2=DEL 3=GND 4=TWR 5=APP/DEP 6=CTR; `_ATIS`/`_OBS`/OBS dropped at ingest. | No client-side ATIS filtering needed. |
| 7 | `qualification_id` = VATSIM rating id (2=S1 … 5=C1 … 7=C3 … 11=SUP). | Not used by the engine today. |

## How the app consumes it (`app/Domain/Activity/Timeline/OwnershipTimelineBuilder`)

- `VatsimGermanyStatsClient::getSessionsInRange($from, $to, $prefixes)` walks `next_cursor` to
  completion (`per_page` 5000, `max_pages` guard).
- `OwnershipTimelineBuilder::ensureRawDays()` fetches missing days in one request per contiguous run,
  buckets rows by the UTC dates they overlap, and caches each day at `activity:sessions-raw:{date}`
  (version-independent — resolution happens on read).
- `buildDay()` resolves each row's callsign (`CallsignResolver`, frequency + `facility_type`) then
  sweeps ownership, caching the result at `activity:timeline:{date}:{data-version}`.
- Config: `activity.sources.sessions.{per_page,max_pages,callsign_prefixes,history_since,volatile_days}`.

## Removed from the app when this landed

`app/Models/AtcSession.php`, the `atc_sessions` migration, `App\Jobs\Activity\SyncAtcSessions`,
`App\Console\Commands\Activity\SyncAtcSessions`, the `activity:sync-sessions` schedule entry, and
`RecalculateControllerActivity`'s per-controller sync step.
