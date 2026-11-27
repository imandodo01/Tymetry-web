# Board and TYMX Exchange — Product and Technical Plan

> Status: Partially delivered — see implementation status below  
> Last updated: 2026-11-27  
> Repository baseline reviewed: `999fcd5e8adfd0b45d72716a3ae306e7c12e5911`

This document records the agreed direction for the next Tymetry Web improvements. It is a planning and contract document, not evidence that the features have been implemented.

## 1. Goals and priorities

The immediate product priorities are:

1. Improve task readability and make it easier to understand the state of work at a glance.
2. Provide a portable task exchange file that can be used in a workflow with ChatGPT.
3. Defer broader operational-readiness work until after these product priorities, while retaining it as a requirement before a production release.

The guiding UX principle is to reduce the mental effort required to read, understand, and organize tasks. Prefer clear presentation and predictable behavior over adding configuration.

## 2. Board view

### 2.1 Product decision

Add a Board view as an alternative presentation of the existing Todo collection. Keep the current List view; do not replace it.

The initial Board has three columns matching the existing application status enum:

| Column | Existing status |
|---|---|
| Pending | `TodoStatus::Pending` |
| In Progress | `TodoStatus::Progress` |
| Done | `TodoStatus::Done` |

The Board is a different view of the same Todo records. It must not introduce a second task lifecycle or duplicate task data.

### 2.2 Card content

The initial card should prioritize information needed to scan tasks quickly:

- Task title.
- Priority.
- Due date and a clear overdue/today indication where applicable.
- A concise description only if it improves scanning without making cards visually dense.

Keep full task editing and less frequently used information in the existing create/edit flow. Do not overload the card with every field or action.

### 2.3 Interaction

- Dragging a card between columns changes only its status.
- Reuse the existing server-side status transition and its validation, ownership checks, completion metadata, and activity logging.
- A successful move must be persisted by the server; a client-only visual move is not sufficient.
- If the request fails, restore the card to its previous column and show a useful error.
- Provide a non-drag status control for touch, keyboard, and assistive-technology users. Drag-and-drop must not be the only interaction.
- Do not add user-defined card ordering in the initial release. Existing Smart Ordering remains the source of order within each status group.

### 2.4 Filters and responsive behavior

- Preserve task search, status, and priority filtering.
- Board and List should represent the same filtered task set.
- Keep the Board usable on desktop, tablet, and mobile.
- On narrow screens, avoid page-level horizontal overflow. Choose a responsive column layout that keeps cards readable; do not assume desktop drag-and-drop translates to touch devices.

### 2.5 Technical direction

- Use the existing Todo model, status and priority enums, ownership model, and activity-log behavior.
- Provide a Board data query/response appropriate to the Board rather than trying to repurpose the server-side DataTables response as card data.
- Scope every Board query to the authenticated user on the server.
- Reuse the existing status update endpoint where its contract fits. If the Board needs a different response contract, keep the underlying authorization and mutation behavior consistent rather than duplicating business rules.
- Avoid adding a drag-and-drop dependency until the implementation phase has compared the existing stack with a small, maintained option. Do not add a dependency merely for visual convenience.

### 2.6 Board acceptance criteria

- [ ] User can switch between List and Board without changing or duplicating tasks.
- [ ] The Board displays Pending, In Progress, and Done columns.
- [ ] Cards show title, priority, and due-date context clearly.
- [ ] Search and priority filters behave consistently with List view.
- [ ] Moving a card persists the new status and updates completion metadata/activity history correctly.
- [ ] Failed moves do not leave the UI in a misleading state.
- [ ] Status can be changed without drag-and-drop.
- [ ] A user cannot see or change another user's tasks through Board queries or mutations.
- [ ] Desktop, tablet, and mobile layouts remain readable and avoid unintended page-level horizontal overflow.
- [ ] Automated tests cover grouping, filters, ownership, and status mutation behavior.

## 3. TYMX task exchange

### 3.1 Naming and file format

The application exchange feature may be referred to as **TMX Import/Export** in the UI and documentation, but the official Tymetry file extension is **`.tymx`**.

The extension avoids colliding with the established Translation Memory eXchange (`.tmx`) format used by translation tools.

The initial TYMX payload is JSON encoded as UTF-8. The extension identifies the Tymetry exchange document; the payload is not XML.

### 3.2 Initial scope

The first exchange version covers Todo records only.

Do not include Projects, Tags, Activity History, attachments, account settings, authentication material, or other entities until their exchange semantics are designed separately.

### 3.3 Export

- Allow export of the user's selected tasks or all tasks in the supported scope.
- Include a schema version so future versions can evolve deliberately.
- Include each task's stable `public_id` as its exchange identity. Do not expose or rely on the internal integer database ID.
- Include user-editable task data needed to recreate the task: title, description, status, priority, due date, and due time.
- Do not export credentials, MFA secrets, recovery codes, session data, or other security-sensitive account data.
- Do not export activity history in the initial version.

### 3.4 Import workflow

Import must be a deliberate, reviewable operation:

1. User selects a `.tymx` file.
2. The server validates file type, size, encoding, JSON structure, schema version, and field values.
3. The application produces an import preview before any data is changed.
4. The preview identifies records that would be created, updated, skipped, or rejected.
5. The user confirms the operation.
6. The server performs the import with ownership checks and reports the final result.

Import must never silently delete existing tasks. A missing task in an import file does not mean that the task should be deleted.

### 3.5 Identity and conflict safety

- Use `public_id` to identify a task across export/import; never trust a supplied internal database ID.
- Match records only within the authenticated user's data.
- Never transfer ownership based on fields in an imported file.
- Detect identity collisions and invalid or unsupported records; do not silently overwrite another user's data.
- Define and test conflict behavior before implementation. The preview must make the intended create/update/skip/reject action visible.
- Treat imported values as untrusted input and apply the same validation and authorization rules as normal task creation and editing.
- Do not permit an import to bypass task lifecycle rules or activity logging.

The exact policy for resolving concurrent edits (for example, whether to skip, replace, or require an explicit per-record choice) remains an implementation contract decision and must be documented before import execution is built.

### 3.6 ChatGPT workflow

The intended workflow is:

1. Export tasks from Tymetry as a `.tymx` file.
2. Provide the file to ChatGPT with a clear instruction.
3. Ask ChatGPT to analyze, categorize, clarify, or propose task updates.
4. Review the proposed changes.
5. Import the returned `.tymx` file into Tymetry and inspect the preview before confirming.

Useful initial scenarios include:

- Grouping tasks by theme or area.
- Rewriting unclear titles and descriptions.
- Suggesting priority based on explicit criteria.
- Breaking a large task into smaller actionable tasks.
- Identifying overdue or potentially conflicting commitments.

ChatGPT suggestions are proposals, not authoritative changes. The user remains responsible for reviewing imported changes. Prompts should explicitly prohibit deleting tasks and changing due dates or priorities without a stated reason and user approval.

### 3.7 Initial schema outline

The following is illustrative, not yet a frozen machine contract:

```json
{
  "format": "tymetry-exchange",
  "schema_version": 1,
  "exported_at": "2026-11-26T00:00:00Z",
  "tasks": [
    {
      "public_id": "018f0000-0000-7000-8000-000000000000",
      "title": "Prepare portfolio",
      "description": null,
      "status": "pending",
      "priority": "high",
      "due_date": "2026-12-01",
      "due_time": null
    }
  ]
}
```

The final schema must define required/optional fields, enum wire values, null handling, date/time semantics, maximum lengths, unknown-field handling, and forward/backward compatibility before implementation.

## 4. Delivery sequence

| Milestone | Scope | Dependency | Status |
|---|---|---|---|
| M1 | Board layout, columns, and readable cards | Existing Todo model and enums | Integrated — PR #51 |
| M2 | Persisted status movement and accessible alternative controls | M1; existing status mutation behavior | Integrated — PR #52 |
| M3 | Filter parity, responsive behavior, loading/empty/error states, and tests | M1–M2 | Core Board scope integrated; see #51/#52 acceptance evidence |
| M4 | Freeze TYMX v1 schema and validation contract | Existing task/date-time contract | Integrated in PR #53 |
| M5 | Export selected/all supported tasks | M4 | Integrated — PR #53 |
| M6 | Import parsing, preview, identity/conflict handling, and confirmation | M4 | Integrated as read-only preview — PR #54 |
| M7 | Import execution, reporting, and security/regression tests | M6 | Not implemented — design required before implementation |

Board work is prioritized before TYMX implementation. Schema decisions should be finalized before building import/export so that the file format does not become an accidental implementation detail.

## 5. Out of scope for the initial delivery

- Replacing the List view.
- Custom columns, custom workflows, swimlanes, or user-defined status values.
- Persistent manual card ordering.
- Task deletion through import.
- Automatic acceptance of ChatGPT-generated changes.
- Import/export of Projects, Tags, Activity History, attachments, or account data.
- Full two-way synchronization or background synchronization between Tymetry and ChatGPT.
- Production deployment or database changes as part of the planning document.

## 6. Operational readiness

Operational readiness remains required before a production release, but it is not the next product-development milestone.

Before production release, verify at minimum:

- Supported MySQL version and migration behavior.
- Real mail delivery and OTP behavior.
- Production-compatible cache/session configuration, including rate limiting and MFA concurrency assumptions.
- Backup and restore procedure using a disposable environment.
- Deployment and rollback procedure.
- Security-sensitive configuration and secret handling.

Do not use production as a substitute for a staging or disposable verification environment.

## 6.1 Delivery status update — 2026-11-27

The original plan above is retained as the product/technical direction. Delivery status is updated here to avoid rewriting historical decisions:

- Board M1 and M2 are integrated into main through PRs #51 and #52.
- Todo due-indicator/completion semantics were corrected and tested in subsequent mainline commits.
- TYMX v1 Export (M5) is integrated through PR #53.
- TYMX M6 import validation and preview is integrated through PR #54. It is strictly read-only and does not mutate Todo or ActivityLog.
- TOTP account rate-limit atomicity is integrated through PR #55; this is security work separate from TYMX.
- M7 Import Apply remains unimplemented. The design gates in Section 3.5 and the issue lifecycle policy remain applicable.

Implementation, automated verification, browser acceptance, and production readiness are distinct. Refer to [TESTING.md](TESTING.md), [REVIEW-FINDINGS.md](REVIEW-FINDINGS.md), and [issue lifecycle/closure policy](engineering/issue-lifecycle-and-closure.md).

## 7. Current implementation baseline and evidence boundary

The source review for this plan used main commit:

`999fcd5e8adfd0b45d72716a3ae306e7c12e5911`

Relevant existing implementation:

- `app/Models/Todo.php`: Todo lifecycle metadata, due-status helpers, and Smart Ordering.
- `app/Enums/Todo/TodoStatus.php`: Pending, In Progress, and Done statuses.
- `app/Enums/Todo/TodoPriority.php`: Low, Medium, and High priorities.
- `app/Http/Controllers/Web/Todo/TodoController.php`: user-scoped Todo operations, status mutation, and activity logging.
- `routes/web.php`: existing Todo routes.
- `resources/views/todo/index.blade.php` and `public/assets/js/todo.js`: current server-side DataTables List view.

This review was source inspection through GitHub. It did not execute local tests, inspect a local working tree, run a browser session, or verify production integrations. Existing manual checks and CI results must remain tied to their own recorded commit SHAs.

## 8. Decisions to preserve

- Readability is the primary UX objective.
- Board is an alternative view over the same Todo data, not a separate task system.
- Existing status values and lifecycle behavior remain authoritative.
- The official Tymetry exchange extension is `.tymx`; the initial payload is versioned UTF-8 JSON.
- The first exchange scope is Todo only.
- Stable `public_id` is the exchange identity; internal database IDs are not part of the public file contract.
- Import requires validation and preview, never silently deletes tasks, and never changes task ownership.
- Operational readiness remains a release gate, but follows the Board and exchange priorities.
