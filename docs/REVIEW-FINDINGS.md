# Code Review Findings

> Review date: 2026-10-20. This is a source-level review of the inspected code, not a report of executed tests. Findings should be reproduced and confirmed before being treated as runtime defects.

## Priority summary

- **High:** status/priority values are not constrained to supported enum values; completion timestamps are inconsistent across completion paths.
- **Medium:** inline priority/due-date controls are not rendered and their JavaScript HTTP verbs disagree with the routes; due-time persistence is incomplete; profile email uniqueness is not validated; bulk inputs need validation; SQLite compatibility is uncertain; OTP is unfinished.
- **Low:** environment-example typo, stale UI copy/handlers, duplicate root route, page-title inconsistencies, and related cleanup.

## Findings

### F-01 — Restrict status and priority values

**Priority:** High  
**Evidence:** `TodoRequest`, `TodoController::updateStatus()`, and `TodoController::updatePriority()` require values but do not restrict them to the supported enum values.  
**Risk:** Invalid values may be persisted and later fail when the Eloquent enum cast is read.  
**Next step:** Validate against the supported enum values and add tests for invalid and valid values.

### F-02 — Keep completion metadata consistent

**Priority:** High  
**Evidence:** `updateStatus()` sets/clears `completed_at`; `toggleComplete()` and `bulkDone()` change status without maintaining the timestamp. Bulk completion and bulk archive also bypass activity logging.  
**Risk:** Completion reports and audit history can disagree with task status.  
**Next step:** Define the expected timestamp/logging behavior and test each completion/archive path.

### F-03 — Inline priority and due-date controls are missing

**Priority:** Medium  
**Evidence:** The controller prepares `priority_action` and `due_action`, but the current DataTables columns do not render them.  
**Risk:** The associated JavaScript listeners have no matching controls in the current table.  
**Next step:** Either render the intended controls or remove unused response/listener code.

### F-04 — Inline update HTTP methods disagree

**Priority:** Medium  
**Evidence:** Priority and due-date routes are registered as `PATCH`, while the current JavaScript handlers send `POST`.  
**Risk:** Requests would be rejected if those controls were rendered.  
**Next step:** Align the route and JavaScript methods, then cover them with feature tests.

### F-05 — Due-time handling is incomplete

**Priority:** Medium  
**Evidence:** The migration includes `due_time` and the request validates it, but the model fillable list and shared form do not expose/persist it.  
**Risk:** The field may be silently ignored or unusable.  
**Next step:** Implement end-to-end only if due time is a product requirement; otherwise remove the unused field/rule.

### F-06 — Profile email uniqueness is not validated

**Priority:** Medium  
**Evidence:** The users table has a unique email constraint, while the profile update validation checks email format but not uniqueness.  
**Risk:** Duplicate email changes can surface as a database exception.  
**Next step:** Add a unique rule that ignores the current user and test duplicate/current email cases.

### F-07 — Login throttling is not visible in the inspected path

**Priority:** Medium  
**Evidence:** The inspected custom login action calls `Auth::attempt()` without a visible limiter in that action or its route.  
**Risk:** Repeated login attempts may not be throttled.  
**Next step:** Verify all route middleware and add explicit throttling if no limiter applies.

### F-08 — SQLite test compatibility needs verification

**Priority:** Medium  
**Evidence:** PHPUnit is configured for in-memory SQLite, while `Todo::scopeSmartOrder()` uses MySQL-specific `CURDATE()`.  
**Risk:** Authenticated todo/dashboard tests may fail under the configured test database.  
**Next step:** Run the affected tests; use a portable query or a compatible test database.

### F-09 — OTP flow is unfinished

**Priority:** Medium  
**Evidence:** OTP controller `send()` and `verify()` methods are empty; the OTP form has no action and its inputs lack `name` attributes.  
**Risk:** OTP routes may look available without implementing a usable flow.  
**Next step:** Treat OTP as a placeholder unless required; if required, implement the flow and tests.

### F-10 — Bulk request validation is incomplete

**Priority:** Medium  
**Evidence:** `bulkDone()` and `bulkArchive()` pass `$request->ids` into `whereIn()` without validating the input shape. Ownership scoping exists; `bulkRestore()` performs more validation.  
**Risk:** Malformed payloads may produce inconsistent errors.  
**Next step:** Validate that IDs are an array of valid identifiers and test empty/malformed payloads and ownership boundaries.

### F-11 — Due-date form markup needs verification

**Priority:** Medium  
**Evidence:** The shared Blade form has an extra quote after the date input's `value` attribute and malformed-looking invalid-feedback markup.  
**Risk:** Rendered markup or validation feedback may be incorrect.  
**Next step:** Inspect rendered HTML and correct the markup with a focused view test if practical.

### F-12 — jQuery AJAX error option is misnamed

**Priority:** Medium  
**Evidence:** Several `$.ajax({ ... })` calls in `todo.js` specify `fail`; jQuery's AJAX options use `error`. A chained `.fail()` is valid.  
**Risk:** The intended error callback may never run.  
**Next step:** Replace option keys with `error` or use a chained `.fail()`, and test failure feedback.

### F-13 — Environment example typo

**Priority:** Low  
**Evidence:** `.env.example` begins with `wAPP_NAME=Laravel` rather than `APP_NAME=Laravel`.  
**Risk:** Copying the example may leave the application name unset.  
**Next step:** Correct the key.

### F-14 — List UI leftovers

**Priority:** Low  
**Evidence:** Duplicate “Status” table headers, bulk-done archive wording, duplicate archive/restore success-toast attempts, and shortcuts targeting stale element IDs.  
**Risk:** Confusing or ineffective UI behavior.  
**Next step:** Clean up after core workflow behavior is covered by tests.

### F-15 — Route and view cleanup

**Priority:** Low  
**Evidence:** The root GET route is declared twice; the edit heading says “Create Todo”; password-recovery pages use “Login” as the document title; OTP CSS uses a relative path rather than Laravel's `asset()` helper.  
**Risk:** Mostly maintainability and presentation inconsistencies.  
**Next step:** Correct in a small cleanup pass.

### F-16 — Stale modal handlers

**Priority:** Low  
**Evidence:** Legacy modal handlers reference controls that are commented out or absent from the current views.  
**Risk:** Dead code adds confusion and can mislead future changes.  
**Next step:** Remove only after confirming no other view uses those controls.

### F-17 — Dashboard metric semantics need a product decision

**Priority:** Low / clarification  
**Evidence:** The “Due Today” query includes completed todos while “Today Tasks” excludes completed tasks.  
**Risk:** Dashboard cards may appear inconsistent if the distinction is unintended.  
**Next step:** Decide whether “Due Today” means all due tasks or only unfinished tasks, then test the chosen definition.

## Verification status

- Findings above are based on source inspection.
- The automated test suite and frontend build have **not** been run as part of this review.
- Confirm each issue in the local environment before making behavior-changing fixes.
- Regression checklist and setup commands: see [TESTING.md](TESTING.md).
