# Code Review Findings

> Disposition updated: 2026-11-27. The original review was performed on 2026-10-20 by source inspection. Status changes below are based on current source and focused regression tests where identified; this is not a claim that every production integration has been manually exercised.

## Status definitions

- **Fixed** — implementation is present and supported by a focused regression test or direct source verification.
- **Implemented** — implementation is present; a dedicated focused test is not recorded.
- **Deferred** — intentionally left outside the stabilization scope.
- **Not a bug** — current product behavior or subsequent inspection invalidated the original finding.
- **Unverified** — evidence is insufficient to change the status.

## Findings disposition

| ID | Original finding | Current status | Evidence / disposition |
|---|---|---|---|
| F-01 | Restrict status and priority values | **Fixed** | `TodoRequest` and AJAX endpoints validate against enum cases. Covered by `TodoRequestTest` and `TodoAjaxEnumValidationTest`. |
| F-02 | Keep completion metadata consistent | **Fixed** | `Todo::saving` maintains `completed_at`; bulk completion iterates model updates and records activity. Covered by `TodoCompletionConsistencyTest`. |
| F-03 | Inline priority and due-date controls are missing | **Fixed** | `TodoController::index()` returns the controls and `todo.js` renders the matching DataTables columns. Covered by `TodoTablePresentationTest`. |
| F-04 | Inline update HTTP methods disagree | **Fixed** | Priority and due-date routes use PATCH; matching JavaScript requests also use PATCH. Source verified in `routes/web.php` and `public/assets/js/todo.js`. |
| F-05 | Due-time handling is incomplete | **Fixed** | `due_time` is fillable, validated, and included in the shared create/edit form. Covered by `TodoDueTimeTest`. Product semantics remain metadata only. |
| F-06 | Profile email uniqueness is not validated | **Fixed** | Profile update uses a unique rule that ignores the current user. Covered by `ProfileUpdateTest`. |
| F-07 | Login throttling is not visible | **Implemented** | Login POST route has `throttle:5,1`. A dedicated login-throttle regression test is not listed in the current test suite. |
| F-08 | SQLite test compatibility needs checking | **Fixed** | Smart ordering uses `CURRENT_DATE`; `TodoSmartOrderTest` exercises the query with the configured test database. |
| F-09 | OTP flow is unfinished | **Fixed** | OTP controller, service, view, and routes implement email verification and optional two-factor challenges. Authentication-flow tests mock the service; deployed email delivery still needs environment smoke testing. |
| F-10 | Bulk request validation is incomplete | **Fixed** | Bulk endpoints validate non-empty arrays of distinct integer IDs and scope records to the authenticated user. Covered by `TodoBulkActionsTest` and `TodoCompletionConsistencyTest`. |
| F-11 | Due-date form markup needs correction | **Fixed** | Shared form markup now has a correctly quoted date value and field-level validation markup. Current source inspected; no dedicated rendered-HTML regression test is recorded. |
| F-12 | jQuery AJAX error option is misnamed | **Fixed** | Current todo AJAX request options use `error` handlers; source verified in `public/assets/js/todo.js`. |
| F-13 | Environment example typo | **Fixed** | `.env.example` begins with `APP_NAME=Laravel`. |
| F-14 | List UI leftovers | **Partially fixed** | Table headers and action labels now describe their controls; the duplicate archive-restore toast was removed in PR #26. Any further cleanup should be based on a reproduced user-facing issue, not the historical source note alone. |
| F-15 | Route and view cleanup | **Partially fixed** | The root route is declared once; edit and password-recovery page titles are corrected; OTP stylesheet uses Laravel's `asset()` helper. Remaining cosmetic cleanup is not required for Sprint 06 closure. |
| F-16 | Stale modal handlers | **Deferred** | Legacy modal markup/comments remain in the Todo view/controller area. No user-facing failure was established; remove only in a separately scoped cleanup after confirming no remaining consumer. |
| F-17 | Dashboard due-today metric semantics | **Not a bug** | Current dashboard due-date lists explicitly exclude Done todos. `DashboardDueTodayTest` asserts completed tasks are not repeated in the open due-date lists. |

## Sprint 06 UX findings

- **M8-A — Feedback consistency:** page-level duplicate success alerts were removed from Todo, Archive, and Profile; archive bulk restore now emits one success toast. PR #26 merged.
- **M8-B — Form/accessibility consistency:** shared Todo form labels target their inputs; Status/Priority validation feedback is visible; Archive checkboxes have accessible names. PR #27 merged.
- **M8-C — Responsive verification:** responsive CSS, compact horizontal navigation, and responsive table wrappers are present. Viewport-specific browser confirmation remains a manual verification item; see [TESTING.md](TESTING.md).

## Verification boundary

- Latest inspected main baseline: `edc29cbfc4ab2ad0a752969b4d08598ea92fd72b` (2026-11-17).
- PRs #26, #27, and #28 are merged.
- GitHub Actions run [#44066718826](https://github.com/imandodo01/Tymetry-web/actions/runs/44066718826) completed successfully for the exact main baseline SHA.
- That run verifies the configured dependency installation, frontend build, and Laravel test commands. It does not establish production mail delivery, real browser behavior, or a MySQL production integration check.
- The assistant inspected the source and CI metadata; it did not execute the local test suite or a real browser session.
- Items marked Implemented or Partially fixed retain the limitations stated in the table; do not interpret this document as a blanket production sign-off.


## Follow-up security and integrity review — 2026-11-27

These are separate workflow-pilot findings, not replacements for the historical F-01–F-17 table above. Their canonical status is tracked in GitHub Issues #39–#41.

### Todo and ActivityLog atomicity — Issue #39

- Single-record mutation and ActivityLog rollback coverage was expanded in PR #48.
- Independent source review found bulk Done, Archive, and Restore paths use transactions and write activity records, but did not find mid-batch failure-injection rollback tests for those three paths.
- Bulk Done and Archive success activity coverage exists; an explicit successful bulk Restore ActivityLog assertion remains missing.
- No direct implementation defect was identified by that source review. The remaining work is regression-test hardening and independent verification.
- Status: **Open — development disposition and QA evidence pending**.

### Email OTP concurrency — Issue #40

- The OTP service uses a 120-second cache-lock lease for send, verify, and clear.
- The configured SMTP timeout is a per-socket-operation timeout and does not bound the full delivery operation.
- The production mail provider's total send timeout and deployed shared cache/lock backend are unknown.
- Lock contention currently maps to the same user-facing invalid/expired code message; consider a distinct retry response.
- SQLite database-cache tests do not establish production MySQL multi-process behavior.
- Status: **Open — release configuration and multi-process QA pending**.

### TOTP MFA — Issue #41

- PR #55 merged the F-01 account-level rate-limit atomicity fix. It serializes account attempt check, verification, failed-attempt increment, and success reset under a per-account cache lock; contention fails closed.
- Exact synchronized PR #55 head 030ca39226ccba80a0827484de224708e084c067 passed CI run [#45956168384](https://github.com/imandodo01/Tymetry-web/actions/runs/45956168384).
- The PR scope explicitly excluded F-02 replay regression coverage. Source review noted the service persists and checks the last-used TOTP counter, but the existing test does not submit the same code/counter a second time.
- Multi-process shared-cache/MySQL verification, authenticator enrollment, recovery-code one-time use, and legacy email-MFA compatibility still require safe-environment evidence.
- Status: **Open — F-01 implemented; F-02 and environment-specific QA pending**.

Do not use these follow-up findings as a production-readiness sign-off. See [Issue Lifecycle and Closure Policy](engineering/issue-lifecycle-and-closure.md) and the linked GitHub issues for closure criteria.
