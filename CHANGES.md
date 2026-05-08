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

## Phase 3 — Gate Guard module *(planned)*

New top-level folder `gate/`:
- `dashboard.php` — entry/exit log + queue
- `checkin.php` — QR scan + plate match against active dispatch
- `incident.php` — flag unauthorised/damaged
- New routes in [index.php](index.php): `gate-*`
- Reuses existing [admin/gate.php](admin/gate.php) UI for the
  dispatcher-side queue view.

## Phase 4 — Dispatcher additions *(planned)*

- Gate-queue panel inside the dispatch dashboard
- Incident flagging with one-click re-assignment to next available
  driver, writing a new `dispatch` row and an `incident.reassigned_d_id`
  pointer.
- Trailer & genset assignment becomes part of the dispatch action;
  gate-guard verifies on exit using `gate_log.verified`.

## Phase 5 — Driver PWA *(planned)*

Layered on top of the existing mobile-friendly driver pages:
- `manifest.webmanifest` + service worker (`/driver/sw.js`) with
  IndexedDB-backed offline queue
- VAPID server keys + `php/crud/add/save_push_subscription.php`
- Camera capture + signature pad on POD page
- One-tap accept / decline (writes `dispatch.driver_accepted_at`)
- Pre-departure checklist gate before driver flips to *Available*
- Status update tap-actions (Picked up / On the way / Arrived /
  Delivered / Issue)
- Gateless-completion page (GPS-required photos)
- Jack-up trailer flow
- Breakdown / cancel with assistance request
- In-app chat against `message`

## Phase 6 — Billing close + client notify *(planned)*

- New `billing/` module: closes a dispatch by stamping
  `billing_closed_at` and rolling up segment-level customer charges.
- Email/SMS hook on close → `client_notified_at`, last
  `workflow_event` stage `client_notified`.
- Reports updated to filter by `workflow_stage`.
