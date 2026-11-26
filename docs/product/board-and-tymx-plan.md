# Board View & Tymetry Exchange (.tymx) — Product and Technical Plan

**Status:** Active delivery plan — implementation and acceptance tracked separately  
**Reconciled:** 2026-11-26  
**Canonical product baseline:** `main` at `92e762fa93a13816a5170fc712c0fabaa5fe8ce4`  
**Documentation update branch:** `feature/m4-tymx-v1-export` (PR #53)

> This document preserves the original product requirements while reflecting current implementation and verification evidence. A milestone is not accepted merely because code exists or CI passes. Browser/device acceptance, independent review, and merge status are tracked separately. The original planning source remains available on `main` until this documentation change is merged.

## 1. Goals and scope

The next product improvement has two parts, in this order:

1. **Board & Readability:** provide a clearer visual way to work with existing Todos, without replacing List View or changing the underlying task model unnecessarily.
2. **Tymetry Exchange (`.tymx`):** provide a portable, reviewable file format for exporting Todos, having them organized externally (including with an AI assistant), and importing proposed changes safely.

The immediate scope is Todo only. Projects, tags, notes, and other future entities are explicitly out of scope for this first iteration.

## 2. Board View

### 2.1 User experience

- Add a Board View alongside the existing List View.
- Board columns are **Pending**, **In Progress**, and **Done**.
- A card should remain deliberately lightweight and readable. Show:
  - Todo title;
  - priority;
  - due date, when present;
  - a clear overdue indicator, when applicable.
- Do not overload cards with every field or action. Provide a way to open the existing Todo detail/edit flow.
- Keep List View available as an alternative presentation of the same Todo records.
- Search, status/priority filters, and Smart Ordering should behave consistently with List View.
- Layout must work on desktop, tablet, and mobile.
- Drag-and-drop is required on pointer-capable devices. It must not be the only way to change status: provide an accessible status control/action for keyboard, touch, and assistive-technology users.

### 2.2 Drag-and-drop behavior

- Dropping a card into another column changes the Todo's persisted status in the database.
- Reuse the existing authenticated status mutation behavior where its response contract is suitable; do not create a parallel status business rule.
- Show a clear dragging/saving state and prevent duplicate submissions for the same card while a change is pending.
- On success, the card belongs in the destination column and the UI reflects the server-confirmed state.
- On failure, restore the card to its original column and show a useful error message.
- Changing columns changes status only. **Do not add manual card ordering, a rank field, or persisted custom ordering in this scope.**

### 2.3 Technical direction

- Use a Board-specific structured data response or query; do not repurpose DataTables HTML output as the card API.
- Reuse existing Todo model, status/priority enums, Smart Ordering scope, authenticated-user ownership scoping, and activity-logged status mutation.
- Every Board read and mutation must be scoped to the authenticated user's Todos and enforced server-side.
- Keep filtering and ordering behavior aligned with the List View. Confirm how Smart Ordering should be represented within each status column during implementation; do not invent a new manual-order contract.
- Inspect current frontend dependencies and browser support before selecting a drag-and-drop implementation. Prefer the smallest maintainable option that meets pointer, touch, keyboard/accessibility, and responsive requirements. Do not add a dependency without evaluating the existing stack first.

### 2.4 Acceptance criteria

- [ ] User can switch between List View and Board View.
- [ ] Board displays exactly the three existing status groups with correct labels.
- [ ] Cards show title, priority, due date (if set), and overdue state (if applicable).
- [ ] Existing search and filters work in Board View and remain consistent with List View.
- [ ] Dragging a card to another column persists the status change server-side.
- [ ] Successful changes update the UI using server-confirmed state.
- [ ] Failed changes restore the original position and display an error.
- [ ] Pending mutations provide feedback and prevent duplicate card updates.
- [ ] Status can also be changed without drag-and-drop.
- [ ] User ownership is enforced for Board data and mutations.
- [ ] List View remains functional and uses the same underlying Todo records.
- [ ] Desktop, tablet, and mobile layouts are usable.
- [ ] Automated tests cover Board authorization/data and status mutation behavior; relevant frontend behavior is verified.
- [ ] No rank/custom-order database field or behavior is introduced.

## 3. Tymetry Exchange (`.tymx`)

### 3.1 Purpose and workflow

The `.tymx` extension is chosen deliberately to avoid collision with the existing `.tmx` Translation Memory eXchange format. The file's internal representation will be JSON.

Intended workflow:

1. User exports selected Todos or all eligible Todos from Tymetry.
2. User provides the `.tymx` file to ChatGPT or another compatible tool for analysis/organization.
3. The tool returns an updated `.tymx` file.
4. User uploads that file into Tymetry.
5. Tymetry validates it and shows an import preview, including proposed changes and conflicts.
6. User explicitly confirms the changes before Tymetry applies them.

The import preview is a required safety boundary; upload must not immediately mutate records.

### 3.2 Initial entity scope

- Todo records only.
- Use Todo `public_id` as the stable external identity.
- Never expose or depend on the internal database primary key as the exchange identity.
- Do not delete Todos through import.
- Do not silently change due dates, due times, priorities, or statuses.
- Other entity types and cross-entity relationships are future work.

### 3.3 TYMX v1 file contract (implemented export contract)

The v1 contract is defined in `docs/schemas/tymx-v1.schema.json` and implemented by the export work on PR #53. The schema and serializer are authoritative; the example below is illustrative. Import is not part of v1 export delivery.

```json
{
  "format": "tymx",
  "version": 1,
  "exported_at": "2026-11-26T12:00:00Z",
  "source": { "application": "Tymetry" },
  "selection": "selected",
  "todos": [{
    "public_id": "019...",
    "base": { "title": "Example", "description": null, "status": "pending", "priority": "medium", "due_date": "2026-12-01", "due_time": "09:30" },
    "data": { "title": "Example", "description": null, "status": "pending", "priority": "medium", "due_date": "2026-12-01", "due_time": "09:30" }
  }]
}
```

Contract rules:
- The envelope requires `format`, `version`, `exported_at`, `source`, `selection`, and `todos`; unsupported or additional fields are rejected by the published schema.
- `format` is `tymx`; `version` is integer `1`; `source.application` is `Tymetry`.
- `selection` is `selected` or `all_active`.
- Each Todo entry contains stable `public_id`, plus `base` and `data` snapshots. Both snapshots contain exactly `title`, `description`, `status`, `priority`, `due_date`, and `due_time`.
- Export initially sets `base` equal to `data`. Separate snapshots support safe future three-way import comparison.
- Wire statuses are `pending`, `in_progress`, and `done`; priorities are `low`, `medium`, and `high`.
- `due_date` is `YYYY-MM-DD` or explicit `null`. `due_time` is local `HH:mm` or explicit `null`; no timezone conversion is performed.
- All six snapshot keys are required. Empty optional values are explicit `null`; omitted keys are invalid.
- `exported_at` is serialized as RFC 3339 / ISO 8601 UTC.
- The `.tymx` file is UTF-8 JSON, not a ZIP archive. Unicode and JSON escaping must be preserved.
- The committed schema is `docs/schemas/tymx-v1.schema.json`. Update schema and compatibility tests together if the contract changes.
### 3.4 Import matching and conflict policy (future import design)

Import preview and apply are not implemented by PR #53. These rules preserve the agreed safety boundary for a separate import workstream. Use `public_id` to match incoming records to existing Todos owned by the authenticated user.

- Matching record: classify proposed changes by comparing imported values with current values.
- Unknown `public_id`: report as unmatched and leave unchanged. The v1 preview does not create Todos. This also prevents a file from claiming an identity that belongs to another account without revealing whether that identity exists elsewhere.
- Duplicate `public_id` values within one file: flag as a file validation error or explicit conflict; never process ambiguously.
- A `public_id` belonging to another user must not reveal that another user's record exists. Treat it as unavailable/unmatched without leaking ownership information.
- Never delete a Todo through import.
- Never silently overwrite fields. Show old and proposed values in preview for every change.
- Distinguish unchanged records, proposed updates, unmatched records, invalid records, and conflicts.
- Use v1 `base` / `data` snapshots for per-field three-way comparison between exported base, proposed data, and current server state. If only the proposal differs from base, the proposal is eligible; if only the server differs, preserve server state; if both differ from base and diverge from each other, report a conflict requiring explicit user choice.
- Import execution must be transactional at a clearly defined unit (prefer the whole confirmed batch unless product design chooses per-record partial success) and must use existing authorization and activity-history conventions.
- Revalidate ownership, record state, and allowed values at execution time; preview is not an authorization boundary.
- Provide a result summary after import. Do not claim all rows were applied if some were skipped or conflicted.

### 3.5 Export behavior (implemented scope)

- Support export of selected Todos and all active Todos within the authenticated user's permitted scope.
- Selected export accepts distinct Todo IDs with a maximum of 100 per request; the List UI initiates export from its visible selection. All-active export excludes archived/soft-deleted records. Both paths are scoped to the authenticated user.
- Export only the six approved Todo fields plus `public_id` and envelope metadata. Exclude internal integer IDs, `user_id`, timestamps, completion timestamp, activity history, and unrelated account/security/private data.
- Use the published v1 schema and deterministic field representation. Initial export sets each record's `base` and `data` snapshots to the same current Todo values.
- Ensure UTF-8 handling and correct JSON escaping.
- Export should not modify source Todos.

### 3.6 Acceptance criteria

- [ ] Export selected Todos to a valid `.tymx` JSON file.
- [ ] Export all eligible Todos to a valid `.tymx` JSON file.
- [ ] Export uses `public_id`, not internal database IDs.
- [ ] Export excludes internal ownership identifiers and unrelated sensitive data.
- [ ] File format has a documented, versioned schema.
- [x] Import preview validates format, version, UTF-8 encoding, file size (5 MB maximum), record count (2,000 maximum), and all v1 field constraints.
- [x] Import preview is shown before any database mutation. Preview is read-only and never writes Todos or ActivityLog records.
- [x] Preview identifies proposed updates, unchanged records, unmatched records, and three-way field conflicts. Invalid documents are rejected with a validation error before a preview is returned.
- [x] Duplicate IDs are rejected; unknown and cross-user IDs receive the same unmatched result without disclosing ownership.
- [ ] Import never deletes Todos.
- [ ] Import never silently changes fields; proposed changes are visible and user-confirmed.
- [ ] Stale-file conflicts are detected using a defined concurrency strategy.
- [ ] Execution revalidates authorization and state and follows transaction/activity-log conventions.
- [ ] Import result reports applied, skipped, and conflicted records accurately.
- [ ] Tests cover valid, malformed, unsupported-version, duplicate-ID, cross-user, stale-data, and partial/transaction failure scenarios as applicable.

## 4. Delivery milestones and current evidence

Work in small, reviewable increments. These states distinguish implementation, technical verification, independent review, and user acceptance. Evidence is tied to the exact branch commit shown.

| Milestone | Scope | Current state | Evidence / exit condition |
|---|---|---|---|
| Board M1 | Board layout, view switching, columns, card presentation | **Merged** | PR #51 merged into `main`. |
| Board M2 | Persisted status movement, drag/drop, non-drag selector | **Implemented; CI passed; acceptance pending** | PR #52 Draft/Open at `e05af79fcdc1ffcbdef1a188b38c72fb0f7cf48a`; Actions run #45783797313 passed on that exact SHA. Browser/device acceptance and independent review remain pending. |
| Board M3 | Filter parity, responsive behavior, accessibility, error/empty/loading states and end-to-end acceptance | **Pending acceptance / remaining checks** | Complete the Board acceptance checklist below; do not infer completion from M2 CI. |
| TYMX M4 | Finalize v1 contract and publish schema | **Implemented on PR #53 branch; CI passed** | Schema: `docs/schemas/tymx-v1.schema.json`; PR #53 exact head `4e4cd60c6f89fb4045613303958deb389e6c7c99`; Actions run #45821229398 passed. Independent review and browser download acceptance remain pending. |
| TYMX M5 | Selected and all-active Todo export | **Implemented; CI passed; acceptance pending** | PR #53 implements authenticated streamed `.tymx` download and feature tests. Manual browser download/round-trip acceptance and independent review remain pending. |
| TYMX M6 | Import validation and preview | **Not started** | Validate format/version/encoding/size/record count/fields and show a no-mutation preview before confirmation. |
| TYMX M7 | Conflict-safe import apply, recovery and result reporting | **Not started** | Implement three-way comparison, apply-time authorization/state revalidation, transaction and activity-log behavior, pre-import backup/recovery, and accurate applied/skipped/conflicted reporting. |

### Board acceptance checklist

- [ ] Desktop drag/drop persists status after reload.
- [ ] Keyboard and mobile status selector works without drag-and-drop.
- [ ] Saving feedback and duplicate-submission prevention behave correctly.
- [ ] Failed request restores server state and shows a useful error.
- [ ] Board-to-List refresh and search/status/priority filter parity are correct.
- [ ] Empty, loading, and error states provide clear feedback and retry where appropriate.
- [ ] Responsive checks at 1280px, 768px, and 390px show no page-level overflow.
- [ ] Cross-user data and mutation isolation are confirmed.
- [ ] Smart Ordering behavior within each column is confirmed against List semantics.
- [ ] Keyboard navigation and assistive-technology labels are usable.

### TYMX export acceptance checklist

- [ ] Browser download produces a valid `.tymx` UTF-8 JSON file.
- [ ] Selected export downloads only selected eligible Todos and enforces the 100-ID limit.
- [ ] All-active export excludes archived/soft-deleted and foreign-user Todos.
- [ ] Unicode text round-trips correctly, including emoji.
- [ ] Envelope and Todo snapshots validate against the committed v1 JSON Schema.
- [ ] No internal integer ID, ownership ID, timestamp, completion timestamp, or activity history is exposed.
- [ ] Export does not mutate source Todos.
- [ ] Manual download and external parse/round-trip acceptance is recorded.
- [ ] Independent review is completed before merge.

## 5. Dependencies and design decisions before implementation

1. Before further implementation, refresh `main`, the active PR head, and any local working tree; the evidence here applies only to the exact SHAs listed above.
2. Inspect project-local instructions (including nested `AGENTS.md` files), current route/controller/view/JS patterns, installed frontend dependencies, and existing tests.
3. Confirm the status mutation response contract and whether it can be reused directly by Board interactions.
4. Decide Board route/data-response shape and view preference behavior.
5. Decide how Smart Ordering is applied within each Board column while preserving current filtering semantics.
6. Treat the committed TYMX v1 JSON Schema and contract above as the current export contract; version any future incompatible change rather than silently changing v1.
7. Before import implementation, specify the full three-way conflict policy, transaction boundary, backup/recovery approach, apply-time revalidation rules, and outcome reporting.
8. Preserve the current import safety decisions: unknown IDs remain unmatched/skipped; no creation, deletion, ownership transfer, or silent overwrite is allowed in the first import iteration.
9. Establish test cases and manual acceptance checks before implementation.

## 6. Explicitly out of scope for this plan

- Projects, tags, notes, and other entity types in `.tymx`.
- Deleting records through import.
- Automatic or silent conflict resolution.
- Background synchronization or direct API integration with ChatGPT.
- Manual Board ordering, drag sorting within a column, or a persisted rank field.
- A wholesale redesign of the existing Todo model or List View.
- Operational-readiness work unrelated to these two feature tracks.

## 7. Repository baseline and evidence

- Repository: `imandodo01/Tymetry-web`.
- `main` baseline used for this reconciliation: `92e762fa93a13816a5170fc712c0fabaa5fe8ce4`.
- The original detailed planning document was retrieved from `main` (blob `dcc24e9cd7731a09f3a65f60c0a480b5b9a265d9`) and used as the basis for this update. The original on `main` remains unchanged until PR #53 is merged.
- Board M1 is merged in PR #51. Board M2 CI run #45783797313 passed on exact PR head `e05af79fcdc1ffcbdef1a188b38c72fb0f7cf48a`; browser/device acceptance and independent review remain pending.
- TYMX export/schema CI run #45821229398 passed on application-code head `4e4cd60c6f89fb4045613303958deb389e6c7c99`. The documentation reconciliation commit `765eadddd84524056e230389f1bdd585e63c38df` was also verified by CI run #45823096586 on that exact head. Manual browser download/round-trip acceptance and independent review remain pending.
- PR #53 remains Draft/Open and has not been merged. This documentation update does not merge it.
- This is a remote GitHub audit. It does not establish the user's local working-tree state, production behavior, or browser/device acceptance.

## 8. Next actions

1. Complete independent review and browser/device acceptance for PR #52 (Board M2/M3), then update this plan with observed results.
2. Complete independent review and manual browser download/round-trip acceptance for PR #53 (TYMX M4/M5). Keep PR #53 Draft until these gates are satisfied.
3. Do not merge either PR solely because CI is green. Merge remains a separate explicit decision.
4. Design TYMX import preview (M6) as a separate workstream. Before implementing apply (M7), finalize transaction boundaries, backup/recovery, apply-time revalidation, and conflict resolution details.
5. Treat Android identity/API/synchronization as a later, separately scoped integration; do not imply it is part of the Web export PR.


### M6 implementation boundary (2026-11-26)

The read-only preview implementation is on [PR #54](https://github.com/imandodo01/Tymetry-web/pull/54), branch `feature/m6-tymx-import-preview`.

- Upload accepts only the `.tymx` extension and validates UTF-8 JSON against the TYMX v1 envelope and Todo snapshot constraints.
- Resource bounds: 5 MB maximum upload and 2,000 Todo records per document.
- Duplicate `public_id` values reject the document.
- Only active Todos owned by the authenticated user are eligible for matching. Unknown, archived, and other-account identities are reported uniformly as unmatched; no ownership information is disclosed and no Todo is created.
- Per-field three-way comparison uses imported `base`, proposed `data`, and current server state. Server-only changes are preserved; proposal-only changes are marked eligible; divergent concurrent changes are marked conflicts.
- The preview endpoint and UI are read-only. There is no apply/confirm action in M6; execution, revalidation, transaction handling, and ActivityLog writes remain M7.
- Schema-invalid documents are rejected as a whole rather than partially previewed.
- CI, independent review, and manual browser acceptance are tracked separately from implementation.
