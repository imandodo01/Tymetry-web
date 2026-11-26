# Board View & Tymetry Exchange (.tymx) — Product and Technical Plan

**Status:** Proposed, documented for planning  
**Prepared:** 2026-11-26  
**Planning baseline:** `main` at `999fcd5e8adfd0b45d72716a3ae306e7c12e5911`  
**Planning branch:** `docs/board-and-tymx-planning`

> This document records the agreed product direction and an implementation plan. It is not evidence that either feature has been implemented or tested. Re-check the repository and local Git state before implementation.

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

### 3.3 Proposed file contract

The following is a planning-level contract, not yet a finalized schema implementation. Exact field names, required/optional rules, enum representation, and schema versioning must be finalized before coding.

```json
{
  "format": "tymx",
  "version": 1,
  "exported_at": "2026-11-26T00:00:00Z",
  "source": {
    "application": "Tymetry"
  },
  "todos": [
    {
      "public_id": "019...",
      "title": "Example task",
      "description": null,
      "status": "pending",
      "priority": "medium",
      "due_date": "2026-12-01",
      "due_time": "09:30:00"
    }
  ]
}
```

Contract considerations to resolve:
- Define a formal JSON Schema (or equivalent server-side validator) for each supported version.
- Decide whether timestamps and timezone metadata are needed for exported/updated task fields. Follow the project's existing date/time/timezone contract; do not reinterpret local dates or times as UTC instants.
- Define whether the format includes an export selection manifest or per-record change metadata.
- Define how omitted fields differ from explicit `null`; omission must not accidentally clear existing values.
- Define how to represent enum values in a stable, human-readable way and validate them against supported values.
- Reject unsupported format versions and malformed payloads with actionable validation feedback.
- Apply size, record-count, string-length, and upload-type limits appropriate to the application.

### 3.4 Import matching and conflict policy

Use `public_id` to match incoming records to existing Todos owned by the authenticated user.

- Matching record: classify proposed changes by comparing imported values with current values.
- Unknown `public_id`: do not silently create a new Todo in the first release unless create semantics are explicitly designed and approved. Default planning assumption: report as unmatched and leave unchanged.
- Duplicate `public_id` values within one file: flag as a file validation error or explicit conflict; never process ambiguously.
- A `public_id` belonging to another user must not reveal that another user's record exists. Treat it as unavailable/unmatched without leaking ownership information.
- Never delete a Todo through import.
- Never silently overwrite fields. Show old and proposed values in preview for every change.
- Distinguish unchanged records, proposed updates, unmatched records, invalid records, and conflicts.
- Consider stale exports: if the record changed in Tymetry after export, detect the conflict rather than blindly overwriting. A reliable strategy (per-field base values, revision token, or equivalent) must be chosen in the detailed design.
- Import execution must be transactional at a clearly defined unit (prefer the whole confirmed batch unless product design chooses per-record partial success) and must use existing authorization and activity-history conventions.
- Revalidate ownership, record state, and allowed values at execution time; preview is not an authorization boundary.
- Provide a result summary after import. Do not claim all rows were applied if some were skipped or conflicted.

### 3.5 Export behavior

- Support export of selected Todos and export of all Todos within the user's permitted scope.
- Export only fields approved by the exchange contract; exclude internal IDs, user IDs, secrets, activity logs, and unrelated private data.
- Use a deterministic, documented field representation and a clear format version.
- Ensure UTF-8 handling and correct JSON escaping.
- Export should not modify source Todos.

### 3.6 Acceptance criteria

- [ ] Export selected Todos to a valid `.tymx` JSON file.
- [ ] Export all eligible Todos to a valid `.tymx` JSON file.
- [ ] Export uses `public_id`, not internal database IDs.
- [ ] Export excludes internal ownership identifiers and unrelated sensitive data.
- [ ] File format has a documented, versioned schema.
- [ ] Import upload validates format, version, encoding, size, record count, and field constraints.
- [ ] Import preview is shown before any database mutation.
- [ ] Preview identifies updates, unchanged records, unmatched records, invalid records, and conflicts.
- [ ] Duplicate IDs and cross-user IDs are handled without ambiguity or information leakage.
- [ ] Import never deletes Todos.
- [ ] Import never silently changes fields; proposed changes are visible and user-confirmed.
- [ ] Stale-file conflicts are detected using a defined concurrency strategy.
- [ ] Execution revalidates authorization and state and follows transaction/activity-log conventions.
- [ ] Import result reports applied, skipped, and conflicted records accurately.
- [ ] Tests cover valid, malformed, unsupported-version, duplicate-ID, cross-user, stale-data, and partial/transaction failure scenarios as applicable.

## 4. Delivery milestones

Work in small, reviewable increments. Re-check the live repository and local Git state before starting; the milestones below are planning order, not a commitment to implement all at once.

| Milestone | Scope | Exit condition |
|---|---|---|
| M1 | Board layout, view switching, columns, card presentation | Readable Board renders existing Todos and List View remains intact |
| M2 | Persistent drag-and-drop status changes | Server mutation, saving feedback, failure rollback, and non-drag status control work |
| M3 | Filters, responsive behavior, accessibility, and tests | Board meets its acceptance criteria across supported interaction modes |
| M4 | Finalize `.tymx` contract and schema; implement export | Versioned export is documented and validated |
| M5 | Import validation and preview | No database mutation occurs before explicit confirmation |
| M6 | Conflict-safe import execution and tests | Confirmed import applies only permitted changes and reports outcomes |

## 5. Dependencies and design decisions before implementation

1. Verify the actual current `main` SHA and reconcile the working tree/branch before editing.
2. Inspect project-local instructions (including nested `AGENTS.md` files), current route/controller/view/JS patterns, installed frontend dependencies, and existing tests.
3. Confirm the status mutation response contract and whether it can be reused directly by Board interactions.
4. Decide Board route/data-response shape and view preference behavior.
5. Decide how Smart Ordering is applied within each Board column while preserving current filtering semantics.
6. Finalize the `.tymx` JSON schema, omitted-vs-null semantics, date/time representation, and version compatibility.
7. Choose and document stale-export conflict detection before implementing import.
8. Define whether unmatched IDs are always skipped in v1 (current recommendation) or whether explicit Todo creation is in scope. Do not add creation implicitly.
9. Establish test cases and manual acceptance checks before implementation.

## 6. Explicitly out of scope for this plan

- Projects, tags, notes, and other entity types in `.tymx`.
- Deleting records through import.
- Automatic or silent conflict resolution.
- Background synchronization or direct API integration with ChatGPT.
- Manual Board ordering, drag sorting within a column, or a persisted rank field.
- A wholesale redesign of the existing Todo model or List View.
- Operational-readiness work unrelated to these two feature tracks.

## 7. Planning baseline and evidence

- Repository: `imandodo01/Tymetry-web`
- Audited `main` baseline: `999fcd5e8adfd0b45d72716a3ae306e7c12e5911` (2026-11-24 commit).
- Existing source audit found Todo status enums, authenticated-user ownership scoping, status mutation with activity logging, and Smart Ordering that can serve as implementation foundations.
- The remote source audit did not inspect the user's local working tree and did not run tests or browser verification. Reconfirm these before implementation.
- No feature code is implemented by this document.

## 8. Next action

After this planning document is reviewed and merged, begin with **M1 Board layout** after a fresh repository and working-tree inspection. Do not start `.tymx` implementation until the Board work is complete or explicitly reprioritized. Before import implementation, complete the unresolved contract and conflict-policy decisions above.
