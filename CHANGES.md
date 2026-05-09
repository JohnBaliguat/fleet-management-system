# Fleet Management — Workflow Refactor Changes

Tracks the staged refactor that adds the 9-stage workflow pipeline,
gate-guard module, driver PWA, and segment-aware bookings while
keeping the existing system running.

---

## Phase 1 — Workflow Foundations *(in progress)*

Goal: clean dead code, lay the database foundation for every later
phase. **No behaviour change.** Existing dispatcher / admin flows
continue to work exactly as before.

### Files removed (16 + 1 directory)

Confirmed duplicates / orphans by name; no inbound references in code
or in the route table at [index.php](index.php).

| Path                                              | Reason                                     |
| ------------------------------------------------- | ------------------------------------------ |
| `index copy.php`                                  | Duplicate of `index.php`                   |
| `.htaccess-`                                      | Backup of `.htaccess`                      |
| `admin/container_monitoring copy.php`             | Pure duplicate                             |
| `admin/container_monitoring1.php`                 | Orphan; route uses `container_monitoring.php` |
| `admin/print_dispatch copy.php`                   | Pure duplicate                             |
| `admin/dispatch-dashboard.php`                    | Orphan; route uses `dispatch-dashboard1.php` |
| `dispatcher/dispatch copy.php`                    | Pure duplicate of `dispatch.php`           |
| `dispatcher/print_job_ticket1.php`                | Orphan; route only uses `print_job_ticket.php` |
| `driver/dashboard with Maptiler.php`              | Orphan map experiment                      |
| `driver/dashboard with google map.php`            | Orphan map experiment                      |
| `driver copy/` (whole directory, 8 files)         | Mirror of `driver/`                        |
| `php/crud/add/addRecord copy.php`                 | Pure duplicate                             |
| `php/crud/add/addRecord copy 2.php`               | Pure duplicate                             |
| `table-fetch/dispatch-table copy.php`             | Pure duplicate                             |
| `table-fetch/dispatch-table copy 2.php`           | Pure duplicate                             |
| `table-fetch/monitoring-table copy 2.php`         | Pure duplicate                             |

### Files kept that *look* like duplicates

These are still referenced and were NOT removed:

- `dispatcher/dispatch-test.php` — used by route `dispatch-dispatchTest`
- `dispatcher/print_dispatch1.php` — used by route `dispatch-print1`
- `php/crud/add/addbooking1.php`, `php/crud/update/updatetruck{1,2,3}.php`,
  `php/crud/update/update_tripDateTime1.php`, the various
  `php/fetch/get_*1.php`/`get_*2.php`, and `table-fetch/*1.php` files
  are referenced from JS/PHP elsewhere. We will revisit consolidation
  in a later phase once the new APIs land.
- `admin/dispatch-dashboard1.php` is the live dispatch dashboard.

### Database migration

New file: [migrations/001_phase1_workflow_foundations.sql](migrations/001_phase1_workflow_foundations.sql)

**This migration is additive only.** No drops, no destructive ALTERs.
Run it against `ptsifleet_db2`:

```bash
mysql -u root ptsifleet_db2 < migrations/001_phase1_workflow_foundations.sql
```

#### New tables (10)

| Table                       | Purpose                                                              |
| --------------------------- | -------------------------------------------------------------------- |
| `gate_log`                  | Every entry/exit, with truck + trailer + genset verification        |
| `gate_queue`                | Vehicles awaiting dispatcher approval                                |
| `incident`                  | Delays, breakdowns, cargo issues, accidents — with re-assignment hook |
| `pre_departure_checklist`   | Fuel / tyres / lights / cargo / genset confirmations                |
| `pod_capture`               | POD photos (×3) + e-signature + GPS                                  |
| `gateless_completion`       | GPS + photos for no-gate destinations, offline-safe timestamps      |
| `trailer_jackup`            | Field-detached trailer; billing keeps running until compound return |
| `message`                   | In-app messaging (driver ↔ dispatcher, group broadcasts)            |
| `push_subscription`         | Web Push (VAPID) endpoints for the driver PWA                       |
| `workflow_event`            | Append-only audit of the 9 pipeline stages                           |

#### Extended tables

- `units` — `unit_type` (`truck` / `genset`). Trucks and gensets share
  this table (e.g. `PM085` is a truck, `GS601` is a genset), distinguished
  only by name prefix. The new column makes the distinction explicit; a
  backfill `UPDATE` sets it from the existing `unit_name` prefix.
- `booking` — `booking_type` (Import/Export/Local), `vessel_name`,
  `voyage_no`, `container_no_port`, `bill_of_lading`, `port_location`,
  `customs_cleared`, `customs_cleared_at`, `client_notified_at`.
- `trips` — `segment_costumer` (per-leg billing), `segment_status`,
  `foul_trip`, `cancelled_at`, `cancelled_reason`, `scheduled_at`.
  *Existing `trips` already encodes per-leg via `d_id` + Trip 1/2/3,
  so we extended it instead of adding a parallel `segments` table.*
- `dispatch` — `workflow_stage`, `workflow_updated_at`,
  `driver_accepted_at`, `driver_declined_at`, `decline_reason`,
  `gate_cleared_at`, `billing_closed_at`, `client_notified_at`.
- `drivers` — `shift_truck`, `shift_started_at`, `last_seen_at`,
  `last_lat`, `last_lng`.

#### Roles

`user.user_type` is `VARCHAR` with no enum constraint; the new
**Gate-Guard** role is allowed without DDL. Phase 3 will add the UI
for assigning it.

---

## Phase 2 — Booking model *(in progress)*

### Booking form — Import / Export / Local picker + port fields

Both [admin/addbooking.php](admin/addbooking.php) and
[dispatcher/addbooking.php](dispatcher/addbooking.php) gained a
`booking_type` dropdown (Local / Import / Export) at the top of the
Add and Edit modals, plus a hidden **Port details** panel that shows
when Import or Export is selected.

Port-panel fields: `vessel_name`, `voyage_no`, `container_no_port`,
`bill_of_lading`, `port_location`, plus a **Customs Cleared** toggle
that only appears for Export bookings (the toggle drives the
`customs_cleared` flag and stamps `customs_cleared_at` on save).

Toggle logic is shared via the `.booking-type-select` class — the
inline jQuery handler in dispatcher/addbooking.php and the script
block at the bottom of admin/addbooking.php both delegate to a single
function so adding more types later means touching one place.

### Backend writers

[php/crud/add/addbooking.php](php/crud/add/addbooking.php) now
persists `booking_type` plus the six port columns. It whitelists the
type against `['Local','Import','Export']` to defend against tampered
POSTs, and clears the port fields when the type is `Local` so a
selection change can't leave stale port data behind. It also emits a
`workflow_event` with stage `order_created` per the Phase 1 audit
schema, so every new booking lands on the timeline.

[php/operations/editbooking.php](php/operations/editbooking.php) was
rewritten with the same fields + sanitisation. `customs_cleared_at`
flips to `NOW()` the first time the flag goes on (and clears when the
flag goes off). Edit handler also blocks `quantity < quantity_use` as
before.

The edit modal previously took ~14 positional arguments via
`onclick="editBooking(...)"`. That was getting unwieldy, so the
booking-table action button now passes only `booking_id` and
`editBooking()` (in [js/addbooking.js](js/addbooking.js)) AJAX-fetches
the full row through the new
[php/fetch/get_booking.php](php/fetch/get_booking.php) endpoint. Same
behaviour, far less coupling between server and client column lists.

### New: Booking Segments editor

A booking can be split into multiple legs. The natural mapping in
this codebase is **one leg = one `trips` row** linked via `d_id` to a
`dispatch` row that carries the driver / truck / trailer / genset
assignment. Phase 1 added segment-level columns
(`segment_costumer`, `segment_status`, `foul_trip`, `cancelled_at`,
`cancelled_reason`, `scheduled_at`) — Phase 2 surfaces them.

New page wired into both roles' sidebars under **Booking Segments**:

- [admin/booking-segments.php](admin/booking-segments.php) → route
  `bookingSegments`
- [dispatcher/booking-segments.php](dispatcher/booking-segments.php) →
  route `dispatch-bookingSegments`
- Shared body: [php/assets/booking_segments_body.php](php/assets/booking_segments_body.php)
- Page JS: [js/booking-segments.js](js/booking-segments.js)

The page lets a dispatcher type or pick a `booking_no`, see a
booking-level summary (with port details for Import/Export), then a
table of every segment under that booking. Each row exposes:

- Client (`segment_costumer`, falls back to dispatch-level customer)
- From / To pickup & destination (datalist of master locations)
- Driver / Truck / Trailer / Genset (with the assignment fields
  bound to the parent `dispatch` row)
- Scheduled-at (datetime-local input)
- Status badge driven by `segment_status` and `foul_trip`

Editing opens a modal. Saving writes `segment_costumer`,
`segment_status`, pickup/destination and `scheduled_at` to `trips`,
and conditionally updates `d_truck` / `d_trailer` / `d_genset` /
`driver_id` / `d_driverName` on the parent `dispatch` row.

### Foul-trip cancellation rule

[php/operations/cancel_segment.php](php/operations/cancel_segment.php)
implements the spec rule: cancelling a segment that was already past
`Pending` flips `foul_trip = 1` and the status to `Foul`; cancelling
a still-pending segment sets `Cancelled` with `foul_trip = 0`. Both
paths stamp `cancelled_at` and `cancelled_reason`, and append a
`workflow_event` (`segment_foul` or `segment_cancelled`) so the
timeline shows the action and reason.

The page's Cancel button surfaces the rule to the user up-front: a
SweetAlert confirms with a different warning for "still Pending" vs
"already Assigned/EnRoute/etc.", so dispatchers don't accidentally
foul-trip a segment that was only created moments ago.

### Backend endpoint summary

| Endpoint                                                                          | Purpose                                                          |
| --------------------------------------------------------------------------------- | ---------------------------------------------------------------- |
| [php/fetch/get_booking.php](php/fetch/get_booking.php)                            | Single-row JSON for the booking edit modal                       |
| [php/fetch/list_booking_nos.php](php/fetch/list_booking_nos.php)                  | Autocomplete datalist for the segment editor's booking picker    |
| [php/fetch/get_segments.php](php/fetch/get_segments.php)                          | Booking summary + all segments (`trips ⋈ dispatch`) for one booking |
| [php/crud/update/update_segment.php](php/crud/update/update_segment.php)          | Save segment + parent dispatch assignment changes                |
| [php/operations/cancel_segment.php](php/operations/cancel_segment.php)            | Cancel with foul-trip rule + workflow_event audit                |

### Things deferred to a later phase

- **Gate-exit block on Export when `customs_cleared = 0`** — the
  customs flag is captured and stamped, but the actual block belongs
  in the Phase 3 gate-guard module, where every exit checks
  authorisation. Wiring it now would mean adding logic in two places.
- **Adding new segments via the editor** — for now segments are
  created as a side-effect of dispatch (existing flow). The editor is
  edit-and-cancel only. We can add a "Split / Add Leg" button in a
  follow-up once the workflow-stage flow stabilises.
- **Segment-level billing rollup** — Phase 6.

## Phase 3 — Gate Guard module *(in progress)*

New top-level folder [gate/](gate/) and a `Gate-Guard` user role.
Existing flows are untouched — this is a brand-new role that lives
alongside Admin / Dispatcher / Driver / etc.

### Pages

| File                                          | Purpose                                               |
| --------------------------------------------- | ----------------------------------------------------- |
| [gate/dashboard.php](gate/dashboard.php)      | Today IN/OUT counters, recent movements, pending queue, open-incident count |
| [gate/checkin.php](gate/checkin.php)          | QR scanner (html5-qrcode) **or** manual plate entry → looks up active dispatch → guard logs IN/OUT |
| [gate/incident.php](gate/incident.php)        | Flag unauthorised vehicles, damaged goods, access violations (with optional photo) |
| [gate/profile.php](gate/profile.php)          | Minimal profile page                                  |
| [gate/sidebar.php](gate/sidebar.php) + [gate/navbar.php](gate/navbar.php) | Role-specific chrome      |
| [gate/_layout_top.php](gate/_layout_top.php) + [gate/_layout_bottom.php](gate/_layout_bottom.php) | Shared layout shells so the role pages stay short |
| [gate/404.php](gate/404.php)                  | Branded 404                                           |

Routes added to [index.php](index.php): `gate-dashboard`, `gate-checkin`,
`gate-incident`, `gate-profile`, `gate-logout`, `gate-login`. A separate
[gate-index.php](gate-index.php) entry point is also provided for parity
with `dispatcher-index.php` / `driver-index.php`, but the main flow goes
through the central `index.php` route table.

### Backend endpoints

| Endpoint                                                                 | Purpose                                                                                |
| ------------------------------------------------------------------------ | -------------------------------------------------------------------------------------- |
| [php/fetch/active_dispatch_by_truck.php](php/fetch/active_dispatch_by_truck.php) | Resolve QR payload → dispatch (tries `d_id`, then `booking_no`, then plate). Joins `booking.booking_type`/`customs_cleared` and computes an `authorised` flag from `workflow_stage` + `gate_queue` |
| [php/operations/gate_checkin.php](php/operations/gate_checkin.php)              | Verify truck + trailer + genset against the dispatch row, write a `gate_log` row, advance workflow (`dispatcher_assigned` → `gate_cleared` on first verified IN; `gate_cleared` → `en_route` on OUT), block Export OUT when `customs_cleared = 0`, consume any matching `gate_queue` entry |
| [php/operations/gate_queue_add.php](php/operations/gate_queue_add.php)          | Guard adds a vehicle to the dispatcher queue when no auth exists yet                   |
| [php/operations/gate_queue_decide.php](php/operations/gate_queue_decide.php)    | Dispatcher / Admin approves or denies a queue entry                                    |
| [php/operations/report_incident.php](php/operations/report_incident.php)        | File a gate incident (with optional photo upload) and emit a `workflow_event(stage='incident_flagged')` |
| [php/fetch/gate_log_recent.php](php/fetch/gate_log_recent.php)                  | Recent movements + today's IN/OUT counters for the dashboard                           |
| [php/fetch/gate_queue.php](php/fetch/gate_queue.php)                            | List queue rows by status (`pending` by default)                                       |
| [php/fetch/incident_list.php](php/fetch/incident_list.php)                      | List incidents (filterable by source: `gate` / `all`)                                  |
| [php/fetch/incident_open.php](php/fetch/incident_open.php)                      | Count of open incidents for the dashboard tile                                         |

### Verification rule

`gate_checkin.php` matches the scanned/typed assignment against the
canonical `dispatch` row. Truck plate, trailer code, and genset code
must all match (empty fields on the dispatch side don't fail the
match — e.g. genset is sometimes optional). Mismatches are logged
**verbatim** in `gate_log.mismatch_reason`, the row is written with
`verified = 0`, and the guard's UI shows the mismatch — the spec calls
for an audit trail rather than silently refusing.

### Customs gate (Phase 2 hook)

When a guard tries to log **OUT** a dispatch whose booking is `Export`
with `customs_cleared = 0`, the endpoint:

1. Writes a `gate_log` row with `verified = 0` and
   `mismatch_reason = "CUSTOMS NOT CLEARED — exit blocked"` (auditable).
2. Returns `status = "error"` so the front-end refuses the action.

### Workflow advances

Verified, authorised gate movements progress the dispatch's
`workflow_stage`:
- IN at `dispatcher_assigned` or `driver_accepted` → `gate_cleared`,
  also stamps `dispatch.gate_cleared_at`.
- OUT at `gate_cleared` → `en_route`.

Each advance writes a `workflow_event` with `actor_role = 'gate_guard'`
so the timeline shows who moved it.

### Dispatcher-side: gate queue panel

[dispatcher/gate.php](dispatcher/gate.php) gained a new bottom panel
that polls `php/fetch/gate_queue.php?status=pending` every 10 s and
exposes Approve / Deny buttons that POST to `gate_queue_decide.php`.
Once approved, the next time the guard scans that truck the
`active_dispatch_by_truck` endpoint reports `authorised = 1`.

### User-management hook

[admin/user.php](admin/user.php) Add and Edit modals gained a
**Gate Guard** option in the role dropdown, so admins can mint guard
accounts without DDL. Login routing in [login-php.php](login-php.php)
now sends `user_type = 'Gate-Guard'` to `gate-dashboard`.

### Things deferred

- **Photo capture from camera in the Incident page** — currently the
  upload uses the standard `<input type="file" capture="environment">`,
  which delegates to the device camera on mobile. A richer in-page
  capture (live preview, multi-shot) belongs in the Phase 5 driver
  PWA where camera-tooling investment is already required.
- **Reassignment from incident** — `incident.reassigned_d_id` is in
  the schema but the dispatcher's reassign-on-incident UI lands in
  Phase 4, where the dispatcher's incident workflow is built out.
- **QR generation for dispatches** — the scanner can already read any
  QR that encodes the `d_id`, `booking_no`, or plate; the dispatcher
  print/dispatch templates will get a QR in a follow-up so guards can
  scan a printed slip instead of asking the driver.

## Phase 4 — Dispatcher additions *(in progress)*

### Gate Queue + Open Incidents tiles on the dispatch dashboard

[dispatcher/dashboard.php](dispatcher/dashboard.php) gained a new row
under the existing Today / Avg / Total cards: two clickable tiles
that auto-refresh every 15 s.

- **Gate Queue (pending)** — count + most recent waiting truck.
  Clicking jumps to the existing gate page where the dispatcher can
  approve/deny.
- **Open Incidents** — open count + the latest incident summary.
  Clicking jumps to the new `dispatch-incidents` page below.

Both tiles use the existing endpoints `gate_queue.php` and
`incident_open.php` / `incident_list.php` from earlier phases — no
new server work needed for the dashboard summary.

### Incidents management page

New shared body [php/assets/incidents_body.php](php/assets/incidents_body.php)
rendered by both:

- [dispatcher/incidents.php](dispatcher/incidents.php) → route `dispatch-incidents`
- [admin/incidents.php](admin/incidents.php) → route `incidents`

Sidebars updated in both roles. The page lists incidents with filter
controls (status: open / acknowledged / resolved / all; severity
filter; refresh button). Each row shows reported time, type +
optional photo link, severity badge, truck/booking, driver,
description, status, and actions:

- **Acknowledge** — flips `incident.status` from `open` to
  `acknowledged` (open-only).
- **Re-assign** — opens the reassign modal (described below).
- **Resolve** — prompts for an optional resolution note, stamps
  `resolved_at = NOW()`.

[js/incidents.js](js/incidents.js) is the page controller. Backend
support uses the upgraded
[php/fetch/incident_list.php](php/fetch/incident_list.php) which now
accepts `status` and `severity` filters as well as `source`.

### One-click re-assignment (the killer Phase 4 feature)

[php/operations/incident_reassign.php](php/operations/incident_reassign.php)
implements the spec rule: "Trigger re-assignment instantly". The
dispatcher picks a new driver (from the Good-status dropdown), a new
truck (datalist of `units` filtered to `unit_type='truck'` and
`unit_status='Good'`), and optionally overrides the trailer/genset.
On submit the endpoint runs a single transaction:

1. Insert a new `dispatch` row mirroring booking metadata
   (`booking_no`, `booking_sn`, `cth_*`, customer, hub) but with the
   new driver/truck and a `workflow_stage = 'dispatcher_assigned'`.
2. Update the original `incident` to point at the new dispatch via
   `reassigned_d_id` and bump it to `acknowledged`.
3. Mark the original dispatch as `workflow_stage = 'reassigned'` so
   it's auditable but visibly superseded.
4. Append two `workflow_event` rows — one `reassigned_from` on the
   original, one `dispatcher_assigned` on the new one — both linking
   back to the incident in the notes.

If the transaction fails at any step it's fully rolled back. The
incidents page then refreshes and the row shows
`Re-assigned → dispatch #N`.

### New endpoints

| Endpoint                                                                       | Purpose                                                   |
| ------------------------------------------------------------------------------ | --------------------------------------------------------- |
| [php/operations/incident_acknowledge.php](php/operations/incident_acknowledge.php) | Open → acknowledged + workflow_event audit                |
| [php/operations/incident_resolve.php](php/operations/incident_resolve.php)     | Mark resolved with optional note                          |
| [php/operations/incident_reassign.php](php/operations/incident_reassign.php)   | Atomic re-assignment described above                      |

### Trailer & genset assignment

The spec has this as Phase 4. The verification is **already enforced
in Phase 3** — [php/operations/gate_checkin.php](php/operations/gate_checkin.php)
fails verification when the truck/trailer/genset on a gate scan
don't match the canonical `dispatch` row. `gate_log.verified = 0`
and `mismatch_reason` carry the audit. No further work needed in
this phase.

### Things deferred

- **Reassign across booking_segments** — currently the new dispatch
  inherits the original booking but the existing `trips` rows still
  point at the original `d_id`. A follow-up could optionally
  duplicate the open trip onto the new dispatch.
- **Severity-based auto-routing** — high-severity incidents could
  trigger a default driver suggestion. Out of scope for the first
  cut; the modal already filters to Good-status drivers.

## Phase 5 — Driver PWA *(in progress)*

The driver experience becomes a Progressive Web App: installable on
home screen, works offline, and surfaces every spec'd action as a
one-tap mobile-first flow.

### PWA shell

| File                               | Purpose                                                                  |
| ---------------------------------- | ------------------------------------------------------------------------ |
| [manifest.webmanifest](manifest.webmanifest) | Install metadata; `start_url=driver-dashboard`, theme-colour, icons |
| [sw-driver.js](sw-driver.js)       | Service worker — at site root so scope covers both driver/* and index.php |
| [driver/pwa-register.js](driver/pwa-register.js) | Registers the SW, surfaces an Online/Offline pill, and (when VAPID configured) subscribes to push |
| [driver/_layout_top.php](driver/_layout_top.php) + [_layout_bottom.php](driver/_layout_bottom.php) | Shared shells for the new mobile pages |

The service worker:
- Caches the app shell (driver dashboard + Bootstrap/jQuery/Sweet-Alert
  CSS+JS) on install so the home screen loads while offline.
- Intercepts POSTs to a curated allowlist of mutation endpoints
  (driver_*, save_pod, save_gateless, save_trailer_jackup,
  save_breakdown, save_pre_departure, send_message). On network
  failure the request is stashed in IndexedDB (full body — including
  multipart files via a JSON-marshalled fallback) and the page gets
  back a synthetic `202 queued` response. When the browser fires
  `sync` (or the page posts a `pt-replay` message after coming back
  online) the queue replays in order.
- Handles `push` events with a clickable notification.

### Driver dashboard wired up

[driver/dashboard.php](driver/dashboard.php) gained:
- `<link rel="manifest">` + Apple/mobile-web-app meta tags
- Per-card workflow badge, plus a stage-aware action row:
  - **Pending** (`dispatcher_assigned` / `reassigned`):
    Accept / Decline buttons.
  - **Accepted** (`driver_accepted` / `gate_cleared` / `en_route`):
    Status pills (Picked up / On the way / Arrived / Delivered) and
    quick-links to POD / Gateless / Jack-up.
- Status taps capture GPS via `navigator.geolocation` (best-effort)
  and POST to the new endpoints; "Delivered" auto-routes to the POD
  page.

### Driver mobile pages

| File                                 | What it does                                                          |
| ------------------------------------ | --------------------------------------------------------------------- |
| [driver/checklist.php](driver/checklist.php) | Truck picker + tap-to-confirm fuel/tyres/lights/cargo/genset, signs the driver as Available |
| [driver/pod.php](driver/pod.php)             | Camera ×3 + a touch-friendly signature pad on `<canvas>`; recipient name; auto GPS |
| [driver/gateless.php](driver/gateless.php)   | GPS lock + 2 mandatory photos; offline-safe via SW queue              |
| [driver/jackup.php](driver/jackup.php)       | GPS + photo for trailer detached at site; opens a `trailer_jackup` row with `billing_active = 1` |
| [driver/breakdown.php](driver/breakdown.php) | Type + severity + assistance request (tow/mechanic/cargo/emergency) + GPS + photo |
| [driver/messages.php](driver/messages.php)   | Polling chat with dispatcher, 5s interval, monotonic `since=msg_id` cursor |

### Backend endpoints

| File                                                                 | Purpose                                                                                          |
| -------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------ |
| [php/operations/_driver_auth.php](php/operations/_driver_auth.php)   | Tiny helper: `require_driver_session()`, `require_post()`, `json_out()` — used by every driver endpoint |
| [driver_accept_job.php](php/operations/driver_accept_job.php)        | Stamps `driver_accepted_at`, advances workflow to `driver_accepted`, audit event                 |
| [driver_decline_job.php](php/operations/driver_decline_job.php)      | Stamps `driver_declined_at`, stores `decline_reason`, audit event                                |
| [driver_update_status.php](php/operations/driver_update_status.php)  | Picked up / On the way / Arrived → keeps `en_route`; Delivered → `delivered`; Issue → no stage change. Updates `drivers.last_lat/lng/last_seen_at` |
| [save_pre_departure.php](php/operations/save_pre_departure.php)      | Inserts a `pre_departure_checklist` row; refuses if any item is unchecked; sets `drivers.shift_truck` and `shift_started_at` |
| [save_pod.php](php/operations/save_pod.php)                          | Photos + signature dataURL → `pod_capture`; advances to `pod_captured`                          |
| [save_gateless.php](php/operations/save_gateless.php)                | GPS + 2 photos → `gateless_completion`; advances to `delivered`                                  |
| [save_trailer_jackup.php](php/operations/save_trailer_jackup.php)    | GPS + photo → `trailer_jackup` (`billing_active = 1`)                                            |
| [save_breakdown.php](php/operations/save_breakdown.php)              | Files an `incident` with `assistance` populated; emits `incident_flagged` audit                  |
| [send_message.php](php/operations/send_message.php)                  | Insert into `message` table; defaults driver→dispatcher, dispatcher→driver                       |
| [php/fetch/messages.php](php/fetch/messages.php)                     | Polling endpoint with `since=msg_id` cursor; auto marks read for the viewer                      |
| [php/crud/add/save_push_subscription.php](php/crud/add/save_push_subscription.php) | Upsert into `push_subscription` keyed by endpoint                                |

### Push notifications — partial

The schema, the SW handler, the subscription save endpoint, and the
client-side subscribe flow are all in place. To turn it on you only
need to:

1. `cp php/config/vapid.example.php php/config/vapid.php`
2. Generate keys (`npx web-push generate-vapid-keys`) and paste them
   into `vapid.php`.
3. Use a server-side push library (e.g. `minishlink/web-push`) to
   actually dispatch notifications from `incident_reassign.php`,
   `gate_queue_decide.php`, and the booking-creation flow. That
   dispatch loop is the one piece I deferred to keep this phase
   scoped — Phase 6 will tie it in alongside billing close + client
   notification.

### Things deferred (intentionally)

- **Server push dispatcher** — see above.
- **Trip Receipts viewer** — the schema currently stores receipt
  references as the free-text `dispatch.d_tripReceipt`. A
  receipts-attached-to-dispatch table belongs in Phase 6.
- **Multi-leg POD** — POD is captured against a `dispatch`. When a
  dispatch has multiple `trips` rows the POD is logically the last
  leg; per-leg POD is straightforward (`save_pod.php` already accepts
  `trip_id`) but the UI to pick a specific leg can wait.
- **Group alerts from operations** — `message.to_role = 'group'`
  works on the schema; broadcasting from the dispatcher side is a
  small follow-up UI.

## Phase 6 — Billing close + client notify *(planned)*

- New `billing/` module: closes a dispatch by stamping
  `billing_closed_at` and rolling up segment-level customer charges.
- Email/SMS hook on close → `client_notified_at`, last
  `workflow_event` stage `client_notified`.
- Reports updated to filter by `workflow_stage`.
