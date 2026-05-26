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

---

## Phase 6 — Billing close, trip receipts, client notify, push wiring *(in progress)*

The final phase wraps the 9-stage workflow loop. With this in,
`Order Created → Dispatcher Assigns → Driver Accepts → Gate Clearance →
En Route → Delivered → POD Captured → Billing Closed → Client Notified`
is end-to-end with a visible audit trail per booking.

### Schema migration

[migrations/002_phase6_billing_receipts_notifications.sql](migrations/002_phase6_billing_receipts_notifications.sql) — additive only.

| Table / column                          | Purpose                                                       |
| --------------------------------------- | ------------------------------------------------------------- |
| `dispatch_receipt`                      | Files attached to a dispatch (manifest, gate pass, customs, etc.). Driver must Acknowledge before going en-route if `requires_ack = 1` |
| `receipt_acknowledge`                   | Per-driver tap audit; unique on `(dr_id, driver_id)`          |
| `push_send_log`                         | Append-only audit of every outbound notification (webpush / email / sms) and its outcome |
| `customer.notify_email` + `notify_phone`| Contact channels for the *Client Notified* step                |
| `dispatch.billing_amount` + `billing_currency` + `billing_notes` | Snapshot of the closed bill so historical totals don't have to be re-derived |

Run with: `mysql -u root ptsifleet_db2 < migrations/002_phase6_billing_receipts_notifications.sql`

### Trip Receipts (the spec's "View Trip Receipts" feature)

| File | Purpose |
|---|---|
| [save_dispatch_receipt.php](php/operations/save_dispatch_receipt.php) | Dispatcher upload (PDF or image) → `php/assets/uploads/receipts/` |
| [acknowledge_receipt.php](php/operations/acknowledge_receipt.php) | Driver tap → upserts a `receipt_acknowledge` row |
| [dispatch_receipts.php](php/fetch/dispatch_receipts.php) | Read API — drivers can only see their own dispatches |
| [driver/receipts.php](driver/receipts.php) | Mobile viewer with per-receipt Acknowledge button |
| [dispatcher/dispatch-receipts.php](dispatcher/dispatch-receipts.php) + [admin/dispatch-receipts.php](admin/dispatch-receipts.php) | Upload UI keyed by `?d_id=` (shared body in [php/assets/dispatch_receipts_body.php](php/assets/dispatch_receipts_body.php)) |

**The en-route gate is enforced.** [driver_update_status.php](php/operations/driver_update_status.php) now refuses any `picked_up`/`on_the_way` transition while there are unacknowledged `requires_ack=1` receipts on the dispatch — returns HTTP 409 with the list of missing acks. The driver dashboard's status pill displays the resulting Swal error.

### Billing close + client notification

| File | Purpose |
|---|---|
| [close_billing.php](php/operations/close_billing.php) | Atomic transaction: stamps `billing_closed_at`, snapshots `billing_amount/currency/notes`, advances `workflow_stage`. With `notify_client=1` it also stamps `client_notified_at` on both `dispatch` and `booking`, advances to `client_notified`, and calls `pt_notify_client()` for email + sms attempts |
| [billing_pending.php](php/fetch/billing_pending.php) | Lists deliverable / closed dispatches with notify-readiness (does the customer have email/phone configured?) |
| [dispatcher/billing.php](dispatcher/billing.php) + [admin/billing.php](admin/billing.php) | Filterable table (Ready to close / Closed / All), close modal with currency, amount, notes, and a "Also notify client" switch (shared body in [php/assets/billing_body.php](php/assets/billing_body.php)) |

### Workflow timeline page

| File | Purpose |
|---|---|
| [workflow_timeline.php](php/fetch/workflow_timeline.php) | Booking summary + every dispatch under it + every `workflow_event` row chronologically |
| [dispatcher/workflow.php](dispatcher/workflow.php) + [admin/workflow.php](admin/workflow.php) | The 9-stage pipeline rendered as horizontal pills (done / current / pending), the dispatch table, and a vertical timeline of audit events. Deep-linkable via `?bn=PTSIBN-00001` (shared body in [php/assets/workflow_body.php](php/assets/workflow_body.php)) |

### Push dispatcher (server-side)

[php/operations/_push_send.php](php/operations/_push_send.php) is the new outbound notification hub:

- `pt_notify_driver()` — Web Push to all of the driver's saved subscriptions. Lazy-loads VAPID keys from `php/config/vapid.php` and the optional `Minishlink\WebPush\WebPush` library. If either is missing, the call is logged in `push_send_log` with `outcome='queued'` and `error='VAPID not configured'` — no exceptions, the calling endpoint succeeds.
- `pt_notify_client()` — looks up `customer.notify_email`/`notify_phone`, sends email via PHP's `mail()`, logs SMS as `outcome='skipped'` (real gateway is out of scope here).
- `pt_notify_dispatchers()` — writes an audit row with `channel='inapp'`; a future dispatcher PWA (or a small SSE bridge) can read these for live banners.

Wired into the existing endpoints:

| Endpoint | Notification |
|---|---|
| [incident_reassign.php](php/operations/incident_reassign.php) | New driver → "New job assigned"; dispatchers → audit |
| [gate_queue_decide.php](php/operations/gate_queue_decide.php) | Driver → "Gate approved/denied" |
| [driver_decline_job.php](php/operations/driver_decline_job.php) | Dispatchers → "Driver declined" |
| [save_breakdown.php](php/operations/save_breakdown.php) | Dispatchers → urgent breakdown alert |
| [close_billing.php](php/operations/close_billing.php) | Driver → "Billing closed"; client → email |

### Routes added

Admin: `billing`, `workflow`, `dispatchReceipts`. Dispatcher:
`dispatch-billing`, `dispatch-workflow`, `dispatch-receipts`. Driver:
`driver-receipts`. Sidebar links wired in both admin and dispatcher.

### To turn push on for real

1. `cp php/config/vapid.example.php php/config/vapid.php`
2. `npx web-push generate-vapid-keys` and paste the values.
3. `composer require minishlink/web-push` at the project root.

After that, every Phase 6 wiring point starts delivering real Web
Push notifications without any code change — `_push_send.php` detects
the library and VAPID config at runtime.

### Things deferred

- **Live SSE / WebSocket panel for dispatchers** — `pt_notify_dispatchers`
  already lands an `inapp` audit row; a small `EventSource`-fed banner
  on the dispatcher dashboard would surface them.
- **SMS gateway** — `customer.notify_phone` is captured and the SMS
  send is logged with `outcome='skipped'`. Wiring to Twilio (or a
  local provider) is a 30-line follow-up in `pt_send_sms()`.
- **Receipt revoke** — receipts can be added but not removed. A small
  delete endpoint behind dispatcher auth will round it out.
- **Per-leg billing rollup** — `dispatch.billing_amount` is a single
  snapshot per dispatch. Multi-segment bookings can have different
  customers per leg; surfacing per-leg subtotals keyed off
  `trips.segment_costumer` is a follow-up.

---

## Phase 7 — Shift, dispatcher gate, POD verification, equipment locations *(in progress)*

Tightens the operational loop the user described:
*Shift → eligible-drivers filter → Trip timer on accept → Gate
confirmation with equipment record → Delivered → Pending Verification
→ Dispatcher Verification → Completed*.

### Schema migration

[migrations/003_phase7_shift_verification_locations.sql](migrations/003_phase7_shift_verification_locations.sql) — additive only.

| Table / column                                                       | Purpose |
| -------------------------------------------------------------------- | ------- |
| `drivers.shift_ended_at`                                             | Pair with `shift_started_at` to mirror the active shift on the drivers row |
| `driver_shift` (new)                                                 | Historical shift records: `started_at`, `ended_at`, `truck_code`, `machine_hours`, `pdc_id` |
| `dispatch.trip_started_at` / `trip_completed_at`                     | Dedicated trip-timer columns; backfilled from `driver_accepted_at` |
| `dispatch.verified_by` / `verified_at` / `verification_notes`        | Dispatcher's POD verification audit |
| `units.current_location` / `current_location_updated_at`             | Where each truck/genset is right now (PTSI Base / Consol Base / In Transit / …) |
| `trailer.current_base` / `current_base_updated_at`                   | Same for trailers |
| `pod_capture.verified_by` / `verified_at` / `verification_notes`     | Mirrors the audit on the POD row itself |

### Driver shift & attendance

- `save_pre_departure.php` now opens a fresh `driver_shift` row on
  every checklist completion. Any unfinished shift on the same driver
  is auto-closed so machine-hour data isn't lost.
- New endpoint **[end_shift.php](php/operations/end_shift.php)** —
  driver clicks End Shift → closes the open `driver_shift` row and
  computes `machine_hours = ROUND((ended_at - started_at) / 60, 2)`.
  Refuses if the driver has an in-flight job (HTTP 409 with the
  blocking booking number); they have to finish or hand off first.
- Driver dashboard surfaces a shift banner above the stats grid:
  green when active (with truck + start time + End Shift button),
  amber when not (with a Start Shift link to the checklist).

### Eligibility filter for dispatching

New endpoint **[dispatchable_drivers.php](php/fetch/dispatchable_drivers.php)**
returns only drivers who:

1. Have an open `driver_shift` (or a `drivers_attendance` row with
   `da_status='Present'` for today), AND
2. Have a non-empty `drivers.shift_truck`.

The dispatcher dashboard's new **Dispatchable Drivers** tile (count
+ link) reads this. Wiring it into the existing dispatch.php picker
is a follow-up — the endpoint is the lever. Filtering is a
client-side change away.

### Trip timer

`driver_accept_job.php` already stamps `driver_accepted_at`; the new
migration backfills `trip_started_at` to mirror it. The dispatcher's
verification page surfaces trip duration as `trip_minutes`. On
verify, **[verify_pod.php](php/operations/verify_pod.php)** stamps
`trip_completed_at = NOW()` so reports can use one column without a
join.

### Gate confirmation → equipment location

`gate_checkin.php` (Phase 3) already verifies truck + trailer + genset
match the dispatch. Phase 7 adds: on a successful scan, also update
the equipment's `current_location`:

- IN at PTSI's gate → equipment set to **PTSI Base** (or whatever the
  guard's `user_assignLocation` says — Consol Base etc.).
- OUT → equipment set to **In Transit**.

Source of truth for the base name is the gate guard's user account's
`user_assignLocation` column.

### Equipment Locations panel

New page (admin + dispatcher) at **[equipment.php](dispatcher/equipment.php)**
backed by **[equipment_locations.php](php/fetch/equipment_locations.php)**.
Lists trucks / gensets / trailers grouped by current base, with a
filter input. Auto-refreshes every 30 s.

### Delivered → Pending Verification → Completed

**`save_pod.php` was changed** so a POD submission no longer flips
the workflow straight to `pod_captured`. It now lands at
**`pending_verification`** with a `workflow_event` of the same name.
The driver's dashboard card shows the new label.

New page **[verifications.php](dispatcher/verifications.php)**
(shared body in [verifications_body.php](php/assets/verifications_body.php)) lists every dispatch
at `pending_verification` with:

- The 2–3 POD photos (zoom-in on click).
- The recipient signature image + typed name.
- A clickable Google-Maps link to the captured POD GPS.
- Trip duration (minutes elapsed since the driver accepted).

**Verify** (button) → **[verify_pod.php](php/operations/verify_pod.php)**
flips `workflow_stage = 'pod_captured'`, stamps `trip_completed_at`,
fills `verified_by` / `verified_at` / `verification_notes` on both
`dispatch` and `pod_capture`, emits a `pod_captured` workflow_event,
and pushes the driver "Trip completed".

**Reject** kicks the dispatch back to `en_route`, emits a
`pod_rejected` event, and pushes the driver "POD needs re-do" with
the reason. The driver can then re-capture from the same POD page.

### Dashboard surfacing

[dispatcher/dashboard.php](dispatcher/dashboard.php) now has a
4-up tile row: Gate Queue / Open Incidents / **Pending Verification**
/ **Dispatchable Drivers**. All four poll every 15 s and link to the
respective page.

### Routes added

Admin: `verifications`, `equipment`. Dispatcher:
`dispatch-verifications`, `dispatch-equipment`. Sidebars updated
in both roles.

### Things deferred

- **Wiring the dispatch.php driver-picker to dispatchable_drivers**
  — the giant 76 KB file is risky to touch; the endpoint is ready
  for a small JS replacement.
- **Per-truck machine-hour history page** — the `driver_shift` rows
  carry the data; a small report rolls it up by `truck_code` over
  any range.
- **Auto-close stale shifts** — a nightly cron that closes any
  open `driver_shift` older than 24 h would prevent runaway hours
  if a driver forgets to tap End Shift.

---

## Phase 9 — Microsoft (Entra ID) work-account SSO *(in progress)*

Adds OpenID Connect login on top of the existing username/password
flow. Office staff (Admin / Dispatcher / HR / Visual / Gate-Guard)
can click **Sign in with Microsoft work account** on the login page
and be authenticated against your tenant. Drivers keep their existing
`drivers_uname` + `drivers_pass` flow (they typically don't have
work accounts).

### Schema migration

[migrations/005_phase9_microsoft_sso.sql](migrations/005_phase9_microsoft_sso.sql) — additive only:
`user.microsoft_oid`, `microsoft_tenant_id`, `microsoft_email`,
`microsoft_linked_at` (+ index on `microsoft_oid`).

### Flow

1. User clicks **Sign in with Microsoft work account** on
   [login.php](login.php).
2. Browser hits `ms-login` route → [ms_sso_start.php](php/operations/ms_sso_start.php) —
   generates a CSRF state, stores it in the session, redirects to
   `https://login.microsoftonline.com/{tenant}/oauth2/v2.0/authorize`
   with scopes `openid profile email offline_access User.Read`.
3. Microsoft authenticates the user and redirects back to
   `index.php?route=ms-callback&code=…&state=…`.
4. [ms_sso_callback.php](php/operations/ms_sso_callback.php):
   - Verifies state matches the session value (defeats login-CSRF).
   - Exchanges the code for tokens via the v2.0 token endpoint.
   - Calls Microsoft Graph `/me` for the canonical profile (id,
     email, displayName).
   - Matches the user in this order:
     1. By stored `microsoft_oid` (the immutable Azure object ID).
     2. By case-insensitive `user_email` match (and binds the OID
        for next time).
     3. Optional auto-provision if the user's tenant matches the
        configured allow-list (defaults to disabled).
   - Sets `$_SESSION` and redirects to the role's dashboard
     (`dashboard` / `dispatch-dashboard` / `hra-dashboard` /
     `gate-dashboard` / …) using the same role-routing logic as
     the password flow.

### Setup (one-time, in Azure)

1. Copy [php/config/microsoft_sso.example.php](php/config/microsoft_sso.example.php) to
   `php/config/microsoft_sso.php` (the real file is **gitignored** —
   it contains a client secret).
2. In **Azure Portal → Microsoft Entra ID → App registrations → New
   registration**:
   - Redirect URI (Web): `http://localhost/Fleet%20Management/index.php?route=ms-callback` (or your prod HTTPS URL).
3. Paste **Application (client) ID** → `MS_CLIENT_ID`.
4. Paste **Directory (tenant) ID** → `MS_TENANT_ID` (or `common` for
   multi-tenant).
5. **Certificates & secrets → New client secret** → paste the *value*
   into `MS_CLIENT_SECRET`.
6. Microsoft Graph `User.Read` is granted by default — nothing else
   to configure.

### Auto-provision (optional)

Set `MS_AUTO_PROVISION_TENANT` to your tenant GUID and
`MS_AUTO_PROVISION_ROLE` (default `Visual`). Users from that tenant
who don't already exist in `user` will be created on first sign-in
with the chosen role; an admin can promote them later via the user
management page.

### Things deferred

- **Driver SSO** — drivers don't use Microsoft work accounts;
  scope was kept to office staff only.
- **id_token signature verification** — we cross-check against
  Microsoft Graph rather than verifying the JWT signature with
  the published JWKS. Acceptable for this flow because we never
  trust the id_token alone, but the JWKS path can be added later
  if needed for stricter compliance.
- **Logout from Microsoft** — current `logout.php` just clears the
  PHP session. Adding `?post_logout_redirect_uri=…` to clear the
  Microsoft cookie too is a one-line follow-up if/when needed.

---

## Phase 10 — Maintenance role + unit blocking *(in progress)*

A new **Maintenance** user role can block trucks, gensets, and
trailers from being picked for dispatch. Block reasons + costs +
history are auditable. Dispatch-side pickers refuse blocked units
automatically.

### Schema migration

[migrations/006_phase10_maintenance.sql](migrations/006_phase10_maintenance.sql) — additive only.

| Table / column                                | Purpose |
| --------------------------------------------- | ------- |
| `units.maintenance_blocked` (TINYINT)         | Fast filter flag — denormalised mirror of the open `unit_maintenance` row |
| `units.maintenance_reason`                    | Free-text shown in pickers / banners |
| `units.maintenance_expected_return` (DATE)    | When dispatch can expect this unit back |
| `units.maintenance_blocked_at`                | When the current block started |
| Same four columns on `trailer`                | Trailers blocked the same way |
| `unit_maintenance` (new table)                | Append-only history per block event (category, severity, reason, photo, expected_return, cost_labor, cost_parts, release_notes, status) |

The block endpoint also sets `unit_status = 'Under Maintenance'`
(or `trailer_status = 'Under Maintenance'`) so legacy queries that
filter on the old status field automatically exclude blocked units.

### Maintenance module

| File | Purpose |
|---|---|
| [maintenance/dashboard.php](maintenance/dashboard.php) | Tiles: blocked count / high-severity / overdue return / open cost. Table of currently blocked units. |
| [maintenance/units.php](maintenance/units.php) | Searchable list of every truck / genset / trailer with Block / Release buttons. Block modal asks for category (engine / brakes / tyres / electrical / body / scheduled / accident / other), severity, expected return, optional photo, and reason. Release modal captures labor + parts cost and notes. |
| [maintenance/history.php](maintenance/history.php) | Every past block event with duration (auto-computed), cost (labor / parts), category, severity. |
| [maintenance/profile.php](maintenance/profile.php) | Minimal profile + logout. |
| [maintenance/sidebar.php](maintenance/sidebar.php) + [navbar.php](maintenance/navbar.php) + [_layout_top.php](maintenance/_layout_top.php) + [_layout_bottom.php](maintenance/_layout_bottom.php) | Role chrome. |

### Backend endpoints

| File | Purpose |
|---|---|
| [block_unit.php](php/operations/block_unit.php) | Atomic transaction: inserts `unit_maintenance` row + updates master mirror. Refuses if already blocked. Accepts optional photo upload. |
| [unblock_unit.php](php/operations/unblock_unit.php) | Closes the latest open `unit_maintenance` row with cost + release notes, resets master mirror to `Good`. |
| [maintenance_units.php](php/fetch/maintenance_units.php) | List view across trucks / gensets / trailers, filterable by kind / status / code. |
| [maintenance_active.php](php/fetch/maintenance_active.php) | Currently-active blocks + summary stats (count / high / overdue / cost) for the dashboard tiles. |
| [maintenance_history.php](php/fetch/maintenance_history.php) | All `unit_maintenance` rows filterable by code. |

### Dispatch-side enforcement

Every picker that previously read trucks / gensets / trailers from
the master tables now also requires `maintenance_blocked = 0`.
Updated files:

- [admin/addbooking.php](admin/addbooking.php), [dispatcher/addbooking.php](dispatcher/addbooking.php), [dispatcher/multibooking.php](dispatcher/multibooking.php)
- [php/fetch/get_trucks.php](php/fetch/get_trucks.php), [get_gensets.php](php/fetch/get_gensets.php), [get_trailers.php](php/fetch/get_trailers.php)
- [php/assets/incidents_body.php](php/assets/incidents_body.php) (Phase 4 reassign picker)
- [php/assets/booking_segments_body.php](php/assets/booking_segments_body.php) (Phase 2 segment editor)
- [driver/checklist.php](driver/checklist.php) (Phase 5 driver shift-truck picker)

### Read-only view for dispatcher / admin

[dispatcher/blocked-units.php](dispatcher/blocked-units.php) and
[admin/blocked-units.php](admin/blocked-units.php) (shared body in
[php/assets/blocked_units_body.php](php/assets/blocked_units_body.php)) — same dashboard view as
the Maintenance role's, no action buttons. Dispatcher dashboard
gained a **Blocked Units** tile linking here.

### Role wiring

- [admin/user.php](admin/user.php) Add/Edit modals expose **Maintenance** in
  the user_type dropdown.
- [login-php.php](login-php.php) routes `user_type = 'Maintenance'` to
  `maintenance-dashboard`.
- [ms_sso_callback.php](php/operations/ms_sso_callback.php) does the same for
  Microsoft SSO users.

### Recommendations not built (deferred)

- **Driver-initiated block request** — driver flags a unit they're
  using as needing service from the PWA; Maintenance approves before
  it actually blocks. UX needs design.
- **Scheduled maintenance triggers** — auto-flag a unit when it hits
  X machine hours (Phase 7 already captures `driver_shift.machine_hours`
  per truck, so the data is there) or X km.
- **Parts inventory linkage** — currently `cost_parts` is a single
  number; a `unit_maintenance_parts` table with line items would
  unlock parts reporting.
- **Push notifications** — when a blocked unit hits its expected
  return date, ping Maintenance + Dispatcher.
- **Block-reason analytics** — pre-built chart of failure category
  over time (currently you can run SQL against `unit_maintenance`).

## Phase 6 — Billing close + client notify *(planned)*

- New `billing/` module: closes a dispatch by stamping
  `billing_closed_at` and rolling up segment-level customer charges.
- Email/SMS hook on close → `client_notified_at`, last
  `workflow_event` stage `client_notified`.
- Reports updated to filter by `workflow_stage`.
