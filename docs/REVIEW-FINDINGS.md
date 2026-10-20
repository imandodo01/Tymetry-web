# Code Review Findings

> Review date: 2026-10-20. This is a source-level review of the inspected code, not a report of executed tests. Findings should be reproduced and confirmed before being treated as runtime defects.

## Priority summary

- **High:** status/priority values are not constrained to supported enum values; completion timestamps are inconsistent across completion paths.
- **Medium:** inline priority/due-date controls are not rendered and their JavaScript HTTP verbs disagree with the routes; due-time persistence is incomplete; profile email uniqueness is not validated; bulk inputs need validation; SQLite compatibility is uncertain; OTP is unfinished.
- **Low:** environment-example typo, stale UI copy/handlers, duplicate root route, page-title inconsistencies, and related cleanup.

## Findings

### F-01 — Restrict status and priority values

**Source location:** `app/Http/Requests/TodoRequest.php` (`rules()`); `app/Http/Controllers/Web/Todo/TodoController.php` (`updateStatus()`, `updatePriority()`).  
**Review status:** Unverified — source inspection only; not runtime-reproduced or test-confirmed.  

**Priority:** High  
**Evidence:** `TodoRequest`, `TodoController::updateStatus()`, and `TodoController::updatePriority()` require values but do not restrict them to the supported enum values.  
**Risk:** Invalid values may be persisted and later fail when the Eloquent enum cast is read.  
**Next step:** Validate against the supported enum values and add tests for invalid and valid values.

### F-02 — Keep completion metadata consistent

**Source location:** `app/Http/Controllers/Web/Todo/TodoController.php` (`updateStatus()`, `toggleComplete()`, `bulkDone()`, `bulkArchive()`).  
**Review status:** Unverified — source inspection only; not runtime-reproduced or test-confirmed.  

**Priority:** High  
**Evidence:** `updateStatus()` sets/clears `completed_at`; `toggleComplete()` and `bulkDone()` change status without maintaining the timestamp. Bulk completion and bulk archive also bypass activity logging.  
**Risk:** Completion reports and audit history can disagree with task status.  
**Next step:** Define the expected timestamp/logging behavior and test each completion/archive path.

### F-03 — Inline priority and due-date controls are missing

**Source location:** `app/Http/Controllers/Web/Todo/TodoController.php` (`index()` DataTables columns); matching todo-table view and JavaScript handler (exact asset/view path still to confirm).  
**Review status:** Unverified — source inspection only; not runtime-reproduced or test-confirmed.  

**Priority:** Medium  
**Evidence:** The controller prepares `priority_action` and `due_action`, but the current DataTables columns do not render them.  
**Risk:** The associated JavaScript listeners have no matching controls in the current table.  
**Next step:** Either render the intended controls or remove unused response/listener code.

### F-04 — Inline update HTTP methods disagree

**Source location:** `routes/web.php` (priority and due-date routes); matching JavaScript AJAX handlers (exact asset path still to confirm).  
**Review status:** Unverified — source inspection only; not runtime-reproduced or test-confirmed.  

**Priority:** Medium  
**Evidence:** Priority and due-date routes are registered as `PATCH`, while the current JavaScript handlers send `POST`.  
**Risk:** Requests would be rejected if those controls were rendered.  
**Next step:** Align the route and JavaScript methods, then cover them with feature tests.

### F-05 — Due-time handling is incomplete

**Source location:** `database/migrations/` (todos migration); `app/Http/Requests/TodoRequest.php` (`rules()`); `app/Models/Todo.php` (`$fillable`); shared todo form view (exact path still to confirm).  
**Review status:** Unverified — source inspection only; not runtime-reproduced or test-confirmed.  

**Priority:** Medium  
**Evidence:** The migration includes `due_time` and the request validates it, but the model fillable list and shared form do not expose/persist it.  
**Risk:** The field may be silently ignored or unusable.  
**Next step:** Implement end-to-end only if due time is a product requirement; otherwise remove the unused field/rule.

### F-06 — Profile email uniqueness is not validated

**Source location:** `app/Http/Controllers/Web/ProfileController.php` (`update()`).  
**Review status:** Unverified — source inspection only; not runtime-reproduced or test-confirmed.  

**Priority:** Medium  
**Evidence:** The users table has a unique email constraint, while the profile update validation checks email format but not uniqueness.  
**Risk:** Duplicate email changes can surface as a database exception.  
**Next step:** Add a unique rule that ignores the current user and test duplicate/current email cases.

### F-07 — Login throttling is not visible in the inspected path

**Source location:** `app/Http/Controllers/Web/Auth/` (custom login controller/action) and `routes/web.php` (login route/middleware); exact limiter coverage remains to verify.  
**Review status:** Unverified — source inspection only; not runtime-reproduced or test-confirmed.  

**Priority:** Medium  
**Evidence:** The inspected custom login action calls `Auth::attempt()` without a visible limiter in that action or its route.  
**Risk:** Repeated login attempts may not be throttled.  
**Next step:** Verify all route middleware and add explicit throttling if no limiter applies.

### F-08 — SQLite test compatibility needs verification

**Source location:** `app/Models/Todo.php` (`scopeSmartOrder()`); `phpunit.xml` (SQLite in-memory test configuration).  
**Review status:** Unverified — source inspection only; not runtime-reproduced or test-confirmed.  

**Priority:** Medium  
**Evidence:** PHPUnit is configured for in-memory SQLite, while `Todo::scopeSmartOrder()` uses MySQL-specific `CURDATE()`.  
**Risk:** Authenticated todo/dashboard tests may fail under the configured test database.  
**Next step:** Run the affected tests; use a portable query or a compatible test database.

### F-09 — OTP flow is unfinished

**Source location:** `app/Http/Controllers/Web/Auth/OtpController.php` (`send()`, `verify()`); OTP Blade view (exact path still to confirm); `routes/web.php` (OTP routes).  
**Review status:** Unverified — source inspection only; not runtime-reproduced or test-confirmed.  

**Priority:** Medium  
**Evidence:** OTP controller `send()` and `verify()` methods are empty; the OTP form has no action and its inputs lack `name` attributes.  
**Risk:** OTP routes may look available without implementing a usable flow.  
**Next step:** Treat OTP as a placeholder unless required; if required, implement the flow and tests.

### F-10 — Bulk request validation is incomplete

**Source location:** `app/Http/Controllers/Web/Todo/TodoController.php` (`bulkDone()`, `bulkArchive()`, `bulkRestore()`).  
**Review status:** Unverified — source inspection only; not runtime-reproduced or test-confirmed.  

**Priority:** Medium  
**Evidence:** `bulkDone()` and `bulkArchive()` pass `$request->ids` into `whereIn()` without validating the input shape. Ownership scoping exists; `bulkRestore()` performs more validation.  
**Risk:** Malformed payloads may produce inconsistent errors.  
**Next step:** Validate that IDs are an array of valid identifiers and test empty/malformed payloads and ownership boundaries.

### F-11 — Due-date form markup needs verification

**Source location:** Shared todo create/edit Blade form (exact path still to confirm); inspect the date input and validation-feedback markup.  
**Review status:** Unverified — source inspection only; not runtime-reproduced or test-confirmed.  

**Priority:** Medium  
**Evidence:** The shared Blade form has an extra quote after the date input's `value` attribute and malformed-looking invalid-feedback markup.  
**Risk:** Rendered markup or validation feedback may be incorrect.  
**Next step:** Inspect rendered HTML and correct the markup with a focused view test if practical.

### F-12 — jQuery AJAX error option is misnamed

**Source location:** JavaScript file containing `$.ajax()` calls for todo actions (exact asset path still to confirm); check each options object using `fail`.  
**Review status:** Unverified — source inspection only; not runtime-reproduced or test-confirmed.  

**Priority:** Medium  
**Evidence:** Several `$.ajax({ ... })` calls in `todo.js` specify `fail`; jQuery's AJAX options use `error`. A chained `.fail()` is valid.  
**Risk:** The intended error callback may never run.  
**Next step:** Replace option keys with `error` or use a chained `.fail()`, and test failure feedback.

### F-13 — Environment example typo

**Source location:** `.env.example` (first environment-variable key).  
**Review status:** Unverified — source inspection only; not runtime-reproduced or test-confirmed.  

**Priority:** Low  
**Evidence:** `.env.example` begins with `wAPP_NAME=Laravel` rather than `APP_NAME=Laravel`.  
**Risk:** Copying the example may leave the application name unset.  
**Next step:** Correct the key.

### F-14 — List UI leftovers

**Source location:** Todo DataTables/view markup and todo JavaScript (exact view/asset paths still to confirm).  
**Review status:** Unverified — source inspection only; not runtime-reproduced or test-confirmed.  

**Priority:** Low  
**Evidence:** Duplicate “Status” table headers, bulk-done archive wording, duplicate archive/restore success-toast attempts, and shortcuts targeting stale element IDs.  
**Risk:** Confusing or ineffective UI behavior.  
**Next step:** Clean up after core workflow behavior is covered by tests.

### F-15 — Route and view cleanup

**Source location:** `routes/web.php` (duplicate root route); todo edit Blade view, password-recovery Blade views, and OTP stylesheet (exact view/CSS paths still to confirm).  
**Review status:** Unverified — source inspection only; not runtime-reproduced or test-confirmed.  

**Priority:** Low  
**Evidence:** The root GET route is declared twice; the edit heading says “Create Todo”; password-recovery pages use “Login” as the document title; OTP CSS uses a relative path rather than Laravel's `asset()` helper.  
**Risk:** Mostly maintainability and presentation inconsistencies.  
**Next step:** Correct in a small cleanup pass.

### F-16 — Stale modal handlers

**Source location:** Legacy modal handlers in todo JavaScript and related Blade views (exact asset/view paths still to confirm).  
**Review status:** Unverified — source inspection only; not runtime-reproduced or test-confirmed.  

**Priority:** Low  
**Evidence:** Legacy modal handlers reference controls that are commented out or absent from the current views.  
**Risk:** Dead code adds confusion and can mislead future changes.  
**Next step:** Remove only after confirming no other view uses those controls.

### F-17 — Dashboard metric semantics need a product decision

**Source location:** `app/Http/Controllers/Web/DashboardController.php` (`index()` and the queries feeding “Due Today” / “Today Tasks”).  
**Review status:** Unverified — source inspection only; not runtime-reproduced or test-confirmed.  

**Priority:** Low / clarification  
**Evidence:** The “Due Today” query includes completed todos while “Today Tasks” excludes completed tasks.  
**Risk:** Dashboard cards may appear inconsistent if the distinction is unintended.  
**Next step:** Decide whether “Due Today” means all due tasks or only unfinished tasks, then test the chosen definition.

## Status definitions

Use these labels consistently as each finding is investigated:

- **Unverified** — identified by source inspection only; not yet reproduced or covered by a focused check.
- **Confirmed** — reproduced or supported by a focused test/check.
- **Fixed** — code changed and a relevant regression check passed.
- **Deferred** — valid issue intentionally postponed, with the reason recorded.
- **Not a bug** — investigation showed the behavior is intentional or the original finding was incorrect.

When updating a finding, retain its priority and evidence, record the exact source path and method/view/handler, change the status only when supported by evidence, and add a short verification note. Do not mark a finding **Fixed** solely because code was edited.

## Verification status

- All findings currently remain **Unverified**. Source locations are provided where confirmed; paths that still need precise identification are explicitly marked.
- The automated test suite and frontend build have **not** been run as part of this review.
- This change improves tracking only; it does not claim that any finding has been reproduced or fixed.
- Regression checklist and setup commands: see [TESTING.md](TESTING.md).
