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

## Phase 2 — Booking model *(planned)*

- Update [admin/addbooking.php](admin/addbooking.php) and
  [dispatcher/addbooking.php](dispatcher/addbooking.php) to expose
  Import / Export / Local picker and conditional port-fields panel.
- Update [php/crud/add/addbooking.php](php/crud/add/addbooking.php) and
  [php/crud/update/](php/crud/update/) booking writers to persist
  the new columns; for Export, block gate exit until
  `customs_cleared = 1`.
- Build a dedicated **Segments** editor on top of the existing
  `segment.php` page so each leg can pick its own client / pickup /
  destination / driver / truck / trailer / genset / scheduled time.
- Implement segment-level *foul-trip* rule (cancel after assignment
  flips `foul_trip = 1`).

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
