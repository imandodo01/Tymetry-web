# Testing Guide

Use this guide to verify the current Tymetry-web behavior. The checklist is not evidence that a test has passed.

## Setup

Requirements: PHP 8.3+, Composer, Node.js/npm, a configured database, and a valid application key.

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configure the database in `.env`, then run:

```bash
php artisan migrate
npm install
npm run build
php artisan test
```

Use a disposable test database for tests that reset or mutate data. Never run destructive test setup against production data.

**Setup note:** `.env.example` currently starts with `wAPP_NAME=Laravel` rather than `APP_NAME=Laravel`. Correct this before relying on the example environment file.

## Automated tests

Run `php artisan test`. Record the date, commit SHA, PHP/database environment, command, result, and any blocker. Passing tests only verify assertions that exist.

The current visible feature test is a basic homepage response test; meaningful todo/authentication regression coverage is still needed. PHPUnit is configured to use SQLite in memory, while `Todo::scopeSmartOrder()` uses MySQL's `CURDATE()`; exercise the affected authenticated pages under the configured test database and make the query portable or configure a compatible test database.

## Manual smoke checklist

### Authentication and profile
- [ ] Guest can open login; valid credentials sign in; invalid credentials show an error.
- [ ] Verify login attempts are appropriately rate-limited; the custom login controller currently calls `Auth::attempt()` without visible throttling.
- [ ] Logout invalidates the session.
- [ ] Password reset works with a valid, expired, or invalid token.
- [ ] Confirm OTP is either implemented end-to-end or clearly treated as unfinished: the current controller's `send()` and `verify()` methods are empty, and the view form has no action or named OTP fields.
- [ ] Profile and password updates persist correctly.
- [ ] Updating a profile email to an address already used by another account produces a validation message rather than a database error.

### Todo lifecycle
- [ ] Create and edit todos; verify required fields and invalid status/priority values are rejected.
- [ ] Filter by title, status, and priority.
- [ ] Verify the visible table controls match the available endpoints. The controller generates `priority_action` and `due_action`, but the current DataTables columns do not render either control.
- [ ] If inline priority/due-date controls are intended, align the JavaScript HTTP verbs with the routes: both routes are registered as `PATCH`, while the JavaScript sends `POST`.
- [ ] Quick-complete and bulk mark-done keep status and completion timestamps consistent.
- [ ] Smart ordering and due-date labels behave correctly for overdue, today, tomorrow, upcoming, and no-date tasks.
- [ ] Archive, restore, and bulk actions work, including empty or malformed selections.
- [ ] Activity logs reflect the actions the product intends to record.

### Ownership and dashboard
- [ ] User A cannot read or mutate User B's todos, including through bulk endpoints.
- [ ] Guests cannot access dashboard, todo, or profile routes.
- [ ] Dashboard counts, due-date lists, and activity are scoped to the signed-in user.
- [ ] Confirm whether the “Due Today” card should include completed tasks; the current query does, while “Today Tasks” excludes completed tasks.
- [ ] Empty states render correctly; no new browser-console or server-log errors appear.

## Source-review findings to verify/fix

These were identified by reading the current source, not by executing the app:

1. **High priority — validate enum values.** `TodoRequest`, `updateStatus`, and `updatePriority` require values but do not restrict them to the supported status/priority enum values. Invalid values can fail later when Eloquent casts are read.
2. **High priority — keep completion metadata consistent.** `toggleComplete` and `bulkDone` update status without maintaining `completed_at`; bulk completion also bypasses activity logging. Define the expected behavior and test all completion paths.
3. **Medium priority — inline priority/due-date controls are not rendered.** The controller returns `priority_action` and `due_action`, but the DataTables column configuration renders neither. The JavaScript listeners therefore have no matching controls in the current table.
4. **Medium priority — inline priority/due-date HTTP methods disagree.** The routes use `PATCH` for both endpoints, but the JavaScript sends `POST`. These requests would be rejected if the controls were rendered.
5. **Medium priority — due time is currently incomplete.** The migration has `due_time` and `TodoRequest` validates it, but the model's fillable list and the shared form do not expose/persist it. Either implement it end-to-end or remove the unused field/validation.
6. **Medium priority — profile email uniqueness is not validated.** The users table has a unique email constraint, but `ProfileController::update()` only validates the email format. A duplicate address can surface as a database exception instead of a validation error.
7. **Medium priority — login throttling is not visible.** The custom login action has no explicit rate-limiting middleware or attempt limiter in the inspected route/controller.
8. **Medium priority — SQLite test compatibility needs checking.** PHPUnit uses in-memory SQLite, but `Todo::scopeSmartOrder()` embeds `CURDATE()`, which is MySQL-specific. Authenticated todo/dashboard tests may fail under SQLite.
9. **Medium priority — OTP flow is unfinished.** The OTP controller methods are empty; the view's form has no action and the six inputs have no `name` attributes. Treat it as a placeholder unless OTP is a required feature.
10. **Medium priority — bulk request validation is incomplete.** `bulkDone` and `bulkArchive` pass `$request->ids` directly into `whereIn()` without validating that it is an array of permitted IDs. Ownership scoping exists, but malformed input should be rejected predictably.
11. **Medium priority — due-date form markup needs correction.** The shared Blade form has an extra quote after the date input's `value` attribute and malformed-looking invalid-feedback markup. Verify rendered HTML and validation errors, then correct it.
12. **Medium priority — AJAX error handlers use the wrong option name.** In `todo.js`, several `$.ajax({ ... })` calls specify `fail`; jQuery's AJAX options use `error`. A chained `.fail()` is valid, but the option is not.
13. **Low priority — environment example typo.** `.env.example` uses `wAPP_NAME` instead of `APP_NAME`.
14. **Low priority — list UI leftovers.** Both status-related table headers are labelled “Status”; bulk-done JavaScript shows archive wording and duplicate success-toast attempts; keyboard shortcuts point to old element IDs that are absent from the current view.
15. **Low priority — route and view cleanup.** The root GET route is declared twice; the edit page heading still says “Create Todo”; password-recovery views also use “Login” as their document title; the OTP stylesheet uses a relative path rather than Laravel's `asset()` helper.
16. **Low priority — stale modal handlers.** The old modal-based create/edit handlers reference controls that are currently commented out or absent.

## Suggested regression tests

Prioritize enum validation, user ownership for single and bulk actions, archive/restore, completion timestamp consistency, route-method compatibility, due-date updates, unique profile email validation, login throttling, and dashboard totals. Re-run the automated suite after each fix and record actual results here or in the PR.
