# Board View & Tymetry Exchange (.tymx) — Product and Technical Plan

**Updated:** 2026-11-26  
**Main baseline:** 92e762fa93a13816a5170fc712c0fabaa5fe8ce4

## Current delivery state

- Board M1 layout is merged (PR #51).
- Board M2 PR #52 is Draft/Open at e05af79fcdc1ffcbdef1a188b38c72fb0f7cf48a. GitHub Actions run #45783797313 passed PHP tests and frontend build on that exact SHA. Browser/mobile acceptance remains pending.
- TYMX v1 contract and export are being implemented on branch feature/m4-tymx-v1-export. Import is not included.

## Board contract

Board is an alternative view of the same Todo records. It uses Pending, In Progress and Done; keeps search/status/priority filters and Smart Ordering consistent with List; changes status through the existing authenticated, activity-logged endpoint; provides pointer drag/drop and a keyboard/touch selector; and does not introduce custom ordering/rank.

### Board acceptance still required

- [ ] Desktop drag/drop persists status after reload.
- [ ] Keyboard and mobile status selector.
- [ ] Saving feedback and duplicate-submission prevention.
- [ ] Failed request restores server state and shows error.
- [ ] Board-to-List refresh and filter parity.
- [ ] Empty/loading/error states and retry.
- [ ] Responsive checks at 1280px, 768px, 390px without page-level overflow.
- [ ] Cross-user data/mutation isolation.

## TYMX v1 contract

- File extension .tymx; payload is UTF-8 JSON.
- Envelope fields: format=tymx, version=1, exported_at (RFC 3339 UTC), source.application=Tymetry, selection=selected|all_active, todos.
- Machine schema: docs/schemas/tymx-v1.schema.json.
- Each Todo record has public_id, base and data. Both snapshots have exactly title, description, status, priority, due_date, due_time.
- Wire values: status pending|in_progress|done; priority low|medium|high.
- Date is YYYY-MM-DD or null. Due time is local HH:mm or null; no timezone conversion.
- Empty optional values are explicit null; omitted keys are invalid.
- Export includes only authenticated user's active Todos. Selected export is limited to current List page, max 100 IDs; all-active excludes archived.
- Stable public_id is the file identity. Internal integer ID, user_id, timestamps, completion timestamp and activity history are excluded.
- Unknown IDs will be skipped on import; duplicate public IDs invalidate; no create, delete, ownership transfer or silent overwrite.
- Import will compare exported base, current server value and proposed data per field. Proposal-only changes are eligible; server-only changes are preserved; divergent concurrent changes are conflicts requiring explicit choice.
- Import preview and apply are not implemented. Before execution, define transaction boundary and pre-import backup/recovery.

## Delivery

| Milestone | State |
|---|---|
| Board M1 layout/view | Complete — PR #51 merged |
| Board M2 persisted movement | Implemented; CI passed; browser/mobile acceptance pending |
| Board M3 filters/responsive/accessibility | Pending manual checks |
| TYMX M4 schema/contract | Implemented on export branch; CI/review pending |
| TYMX M5 selected/all-active export | Implementation in progress |
| TYMX M6 import validation/preview | Not started |
| TYMX M7 conflict-safe apply and recovery | Not started |

Do not merge/release branches until exact-head CI, review and required acceptance gates are satisfied. Android identity/API/sync is a later separate workstream.
